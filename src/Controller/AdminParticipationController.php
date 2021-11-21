<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Diagnostic;
use App\Entity\Participation;
use App\Entity\User;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/participation", name="admin_participation_")
 */
class AdminParticipationController extends AbstractController
{
    /*** @var ScoringService */
    private $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function newParticipation(Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {
            $r = $request->request;

            foreach ($r->get('users') as $user)
                foreach ($r->get('diagnostics') as $diagnostic) {
                    $participation = (new Participation())->setUser($em->getRepository(User::class)->find($user))
                        ->setDiagnostic($em->getRepository(Diagnostic::class)->find($diagnostic));
                    $em->persist($participation);
                }

            $em->flush();

            return $this->redirectToRoute('admin_participations');
        }

        return $this->render('admin/participation_new.html.twig', [
            'active'      => 'participation',
            'users'       => $em->getRepository(User::class)->findBy([], ['username' => 'ASC']),
            'diagnostics' => $em->getRepository(Diagnostic::class)->findBy([], ['lastUpdate' => 'DESC'])
        ]);
    }


    /**
     * @Route("/over-view/{participation}", name="over-view", methods={"GET"})
     */
    public function overViewParticipation(Participation $participation, EntityManagerInterface $em): Response
    {
        $this->scoringService->getResults($participation, true);

        $interval = $participation->getMeta()['time'];
        $participation->updateMeta('time', $interval->d == 0 ? $interval->format('%Hh%im%ss') : $interval->format('%dJours, %Hh%im%ss'));

        return $this->render('admin/participation_over-view.html.twig', [
            'active'        => 'participation',
            'participation' => $participation,
            'categories'    => $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC'])
        ]);
    }


    /**
     * @Route("/reset/{participation}", name="reset", methods={"GET"})
     */
    public function resetParticipation(Participation $participation, EntityManagerInterface $em): Response
    {
        $reset = $participation->getMeta() !== null && isset($participation->getMeta()['reset']) ? $participation->getMeta()['reset']+1 : 1;
        $participation->setAnswers([]);
        $participation->setMeta(['reset' => $reset]);
        $participation->setDone(false);

        $em->persist($participation);
        $em->flush();

        return $this->redirectToRoute('admin_participations');
    }


    /**
     * @Route("/switch/{search}", name="switch", methods={"GET"})
     */
    public function switchSearch(string $search, EntityManagerInterface $em): Response
    {
        return $this->render("admin/participation_switch_$search.html.twig", [
            'users' => $em->getRepository(User::class)->findBy([], [$search => 'ASC'])
        ]);
    }


    /**
     * @Route("/delete/{participation}", name="delete", methods={"GET", "POST"})
     */
    public function deleteParticipation(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($participation);
        $em->flush();

        return $this->redirectToRoute('admin_participations');
    }
}

<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Diagnostic;
use App\Entity\Participation;
use App\Entity\User;
use App\Service\CsvService;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\String\b;

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
            'users'       => $em->getRepository(User::class)->findBy(['activated' => 1], ['id' => 'DESC']),
            'diagnostics' => $em->getRepository(Diagnostic::class)->findBy([], ['lastUpdate' => 'DESC'])
        ]);
    }


    /**
     * @Route("/over-view/{participation}", name="over-view", methods={"GET"})
     */
    public function overViewParticipation(Participation $participation, EntityManagerInterface $em): Response
    {
        if($participation->getDone() === false) return $this->redirectToRoute('admin_participations');

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
     * @Route("/download/{participations}", name="download", methods={"GET"}, defaults={"participations": ""})
     * @throws Exception
     */
    public function downloadParticipations(?string $participations, EntityManagerInterface $em): Response
    {
        try {
            $content        = "";
            $diagnosticId   = null;
            $categories     = $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']);

            // NOTE Get participations Entities / bind errors
            $participations = explode("|", $participations);
            foreach ($participations as $key => $participation) {
                $id = $participation;
                $participation = $em->getRepository(Participation::class)->find($id);
                if($participation === null || $participation->getDone() === false)
                    throw new Exception("Error participation n°$id", 400);
                else
                    if($diagnosticId !== null && $participation->getDiagnostic()->getId() !== $diagnosticId)
                        throw new Exception("Error diagnostic : unique diagnostic required", 400);
                    else
                        $diagnosticId = $participation->getDiagnostic()->getId();

                $participations[$key] = $participation;
            }

            // NOTE build CSV header
            $content .= CsvService::buildHeader($participations[0], $categories) . "\n";

            // NOTE concat participations' lines
            foreach ($participations as $key => $participation)
            {
                $this->scoringService->getResults($participation, true);
                $interval = $participation->getMeta()['time'];
                $participation->updateMeta('time', $interval->d == 0 ? $interval->format('%Hh%im%ss') : $interval->format('%dJours, %Hh%im%ss'));

                $content .= CsvService::concatParticipation($participation, $categories);
                $content .= $key < count($participations) -1 ? "\n" : "";
            }

        } catch (Exception $e) {
            //throw new \Exception($e);
            return new Response($e->getMessage(), 400, [
                'Content-Type' => 'text/json'
            ]);
        }

        // NOTE build file AND return
        $filename = "export-csv-" . uniqid() . ".csv";
        $file = fopen($this->getParameter('csv_dir') . $filename, "w");
        fwrite($file, $content);
        fclose($file);

        return new Response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' .$filename. '"'
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
     * @Route("/switch", name="switch", methods={"GET"})
     */
    public function switchSearch(Request $request, EntityManagerInterface $em): Response
    {
        $sort = $request->query->get('sort', 'id');
        $by   = $request->query->get('by', 'DESC');

        return $this->render("admin/participation_switch_$sort.html.twig", [
            'users' => $em->getRepository(User::class)->findBy(['activated' => 1], [$sort => $by])
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

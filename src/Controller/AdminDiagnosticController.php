<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Diagnostic;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/diagnostic", name="admin_diagnostic_")
 */
class AdminDiagnosticController extends AbstractController
{
    /**
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function newDiagnostic(Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {
            $diagnostic = (new Diagnostic())
                ->setName($request->request->get('name'))
                ->setComment($request->request->get('comment'));

            $em->persist($diagnostic);
            $em->flush();

            return $this->redirectToRoute('admin_diagnostic_edit', ['diagnostic' => $diagnostic->getId()]);
        }

        return $this->render('admin/diagnostic_new.html.twig', [
            'active'     => 'diagnostics'
        ]);
    }


    /**
     * @Route("/edit/{diagnostic}", name="edit", methods={"GET", "POST"})
     * @throws NonUniqueResultException
     */
    public function editDiagnostic(Diagnostic $diagnostic, Request $request, EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']);

        if($request->getMethod() === "POST") {
            $r = $request->request;

            $categoriesScales = [];
            foreach ($categories as $category) {
                $cId = $category->getId();
                // NOTE Get categories' scales
                if ($r->get("$cId-scales") !== null)
                    $categoriesScales[$cId] = $r->get("$cId-scales");
            }

            // NOTE Get global' label & scale
            $globalScales = [];
            foreach ($r->get('global-label') as $key => $label)
                $globalScales[] = ['label' => $label, 'scale' => $r->get('global-scales')[$key]];

            $diagnostic
                ->setName($r->get('name'))
                ->setComment($r->get('comment'))
                ->setCategoriesScales($categoriesScales)
                ->setGlobalScale((array)$globalScales)
                ->setLastUpdate(new \DateTime());

            $em->persist($diagnostic);
            $em->flush();
        }

        // NOTE Get last update Catg. from question
        $lastUpdate = $em->getRepository(Question::class)->getLastUpdate($diagnostic);
        $lastUpdate = $lastUpdate !== null ? $lastUpdate->getCategory()->getId() : 1;

        return $this->render('admin/diagnostic_edit.html.twig', [
            'active'     => 'diagnostics',
            'diagnostic' => $diagnostic,
            'categories' => $categories,
            'lastUpdate' => $lastUpdate
        ]);
    }


    /**
     * @Route("/delete/{diagnostic}", name="delete", methods={"GET", "POST"})
     */
    public function deleteQuestion(Diagnostic $diagnostic, Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {
            $em->remove($diagnostic);
            $em->flush();

            return $this->redirect($request->request->get('referrer'));
        }

        return $this->render('admin/diagnostic_delete.html.twig', [
            'active'         => 'diagnostics',
            'diagnostic'     => $diagnostic,
            'participations' => $diagnostic->getParticipations()->count(),
            'referrer'       => $request->server->get('HTTP_REFERER')
        ]);
    }
}

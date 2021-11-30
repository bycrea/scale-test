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
            $diagnostic = $em->getRepository(Diagnostic::class)->findBy(['name' => $request->request->get('name')]);
            if(!empty($diagnostic))
                return $this->render('admin/diagnostic_new.html.twig', [
                    'active'    => 'diagnostics',
                    'error'     => 'Ce nom existe déjà'
                ]);

            $diagnostic = (new Diagnostic())
                ->setName($request->request->get('name'))
                ->setComment($request->request->get('comment'));

            $em->persist($diagnostic);
            $em->flush();

            return $this->redirectToRoute('admin_diagnostic_edit', ['diagnostic' => $diagnostic->getId()]);
        }

        return $this->render('admin/diagnostic_new.html.twig', [
            'active'    => 'diagnostics'
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
        $lastUpdate = $lastUpdate !== null ? "#Q".$lastUpdate->getId() : "";

        // NOTE Get QLinked & QNexted questions
        $isQLinked = $isQNexted = [];
        foreach ($diagnostic->getQuestions() as $q)
        {
            if(!empty($QLink = $q->getQlink()))
                $isQLinked[] = $em->getRepository(Question::class)->find($QLink[0])->getId();

            if(!empty($QNext = $q->getQnext()))
                $isQNexted[] = $em->getRepository(Question::class)->find($QNext[0])->getId();
        }

        return $this->render('admin/diagnostic_edit.html.twig', [
            'active'     => 'diagnostics',
            'diagnostic' => $diagnostic,
            'categories' => $categories,
            'lastUpdate' => $lastUpdate,
            'isQLinked'  => $isQLinked,
            'isQNexted'  => $isQNexted
        ]);
    }


    /**
     * @Route("/copy/{diagnostic}", name="copy", methods={"GET"})
     */
    public function copyDiagnostic(Diagnostic $diagnostic, EntityManagerInterface $em): Response
    {
        $newDiagnostic = (new Diagnostic())
            ->setName($diagnostic->getName() . " copy")
            ->setCategoriesScales($diagnostic->getCategoriesScales())
            ->setGlobalScale($diagnostic->getGlobalScale());

        foreach ($diagnostic->getQuestions() as $question)
        {
            $clone = clone $question;
            $clone->unsetId()->setDiagnostic($newDiagnostic)->setCreatedAt(new \DateTime())->setLastUpdate(new \DateTime());
            $em->persist($clone);
        }

        $em->persist($newDiagnostic);
        $em->flush();

        return $this->redirectToRoute('admin_diagnostics');
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

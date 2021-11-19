<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Diagnostic;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/question", name="admin_question_")
 */
class AdminQuestionController extends AbstractController
{
    /**
     * @Route("/new/{diagnostic}/{category}", name="new", methods={"GET", "POST"})
     * @throws Exception
     */
    public function newQuestion(Diagnostic $diagnostic, Category $category, Request $request, EntityManagerInterface $em): Response
    {
        return $this->render('admin/question_edit.html.twig', [
            'active'       => 'diagnostics',
            'diagnostic'   => $diagnostic,
            'category'     => $category,
            'categories'   => $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']),
            'answerTypes'  => Question::ANSWERTYPES
        ]);
    }


    /**
     * @Route("/edit/{diagnostic}/{category}/{question}", name="edit", defaults={"question": "null"}, methods={"GET", "POST"})
     */
    public function editQuestion(Diagnostic $diagnostic, Category $category, ?Question $question, Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {
            try {
                $r = $request->request;
                $newCategory = $em->getRepository(Category::class)->find($r->get('category'));
                $isNewCat    = $newCategory !== $category;

                // NOTE save basics
                $question = $question ?? new Question();
                $question
                    ->setLastUpdate(new \DateTime())
                    ->setAsk($r->get('ask'))
                    ->setHelper($r->get('helper'))
                    ->setCategory($newCategory)
                    ->setRequired($r->get('required', false) == 'true')
                    ->setAnswerType($r->get('answerType'))
                    ->setCategoryFactor($r->get('categoryFactor', 0))
                    ->setGlobalFactor($r->get('globalFactor', 0))
                    ->setQlink($r->get('qlink', null))
                    ->setQnext($r->get('qnext', null))
                    ->setDiagnostic($diagnostic);

                // NOTE save answers
                $answers = [];
                $scores  = $r->get('scores', []);
                foreach ($r->get('answers', []) as $key => $answer) $answers[$key] = ['answer' => $answer, 'score' => $scores[$key]];
                $question->setAnswers($answers);

                // NOTE Inactivate Nexted question
                if(!empty($QNext = $question->getQnext())) {
                    $nexted = ($em->getRepository(Question::class)->find($QNext[0]))->setActivated(false);
                    $em->persist($nexted);
                }

                // NOTE Activate Linked question
                if(!empty($QLink = $question->getQlink())) {
                    $linked = ($em->getRepository(Question::class)->find($QLink[0]))->setActivated(true);
                    $em->persist($linked);
                }

                $em->persist($question);
                $em->flush();

                // NOTE IF new question OR newCategory => re-order rang for all questions
                if($question->getRang() === null || $isNewCat)
                {
                    $lastInCat = $em->getRepository(Question::class)->lastInCat($diagnostic, $question);
                    $question->setRang($lastInCat ? $lastInCat->getRang() + 1 : 1);
                    $em->persist($question);
                    $em->flush();

                    $i = 1;
                    $categories = $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']);
                    foreach ($categories as $c)
                    {
                        $questions = $em->getRepository(Question::class)->findBy(['diagnostic' => $diagnostic, 'category' => $c], ['rang' => 'ASC']);
                        foreach ($questions as $q)
                        {
                            $q->setRang($i);
                            $em->persist($q);
                            $i++;
                        }
                    }
                    $em->flush();
                }

            } catch (Exception $e) {
                throw new Exception($e);
            }

            return $this->redirectToRoute('admin_diagnostic_edit', ['diagnostic' => $diagnostic->getId()]);
        }

        $response   = $this->forward('App\Controller\AdminAnswerController::editAnswerType', ['question'  => $question->getId()]);
        $answerHTML = $response->getContent();

        return $this->render('admin/question_edit.html.twig', [
            'active'       => 'diagnostics',
            'diagnostic'   => $diagnostic,
            'category'     => $question->getCategory(),
            'answerTypes'  => Question::ANSWERTYPES,
            'question'     => $question,
            'categories'   => $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']),
            'answerHTML'   => $answerHTML,
            'error'        => $request->getMethod() === "PATCH" ? $request->request->get('error') : null
        ]);
    }


    /**
     * @Route("/delete/{question}", name="delete", methods={"GET", "POST"})
     */
    public function deleteQuestion(Question $question, Request $request, EntityManagerInterface $em): Response
    {
        $diagnostic = $question->getDiagnostic();
        $participations = $diagnostic->getParticipations()->count();

        if($request->getMethod() === "POST") {

            // NOTE delete question
            $em->remove($question);
            $em->flush();

            // NOTE re-order questions rang
            $questions = $em->getRepository(Question::class)->findBy(['diagnostic' => $diagnostic], ['rang' => 'ASC']);
            for ($i=0; $i<count($questions); $i++) {
                $questions[$i]->setRang($i + 1);
                $em->persist($questions[$i]);
            }
            $em->flush();

            return $this->redirect($request->request->get('referrer'));
        }

        return $this->render('admin/question_delete.html.twig', [
            'active'         => 'diagnostics',
            'question'       => $question,
            'participations' => $participations,
            'referrer'       => $request->server->get('HTTP_REFERER')
        ]);
    }

    /**
     * @Route("/toggle/{question}", name="toggle", methods={"PATCH"})
     * @throws Exception
     */
    public function toggleQuestion(Question $question, EntityManagerInterface $em): Response
    {
        $question->setActivated(!$question->getActivated());
        $em->persist($question);
        $em->flush();

        return new JsonResponse(true);
    }

    /**
     * @Route("/move", name="move", methods={"PATCH"})
     * @throws Exception
     */
    public function moveQuestion(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data['order'] as $id => $rang)
        {
            $question = $em->getRepository(Question::class)->find($id);
            if($question === null) throw new Exception("Wrong param id : $id", 400);

            if($question->getId() == $data['dragged'] && $question->getCategory()->getId() !== $data['newCategory'])
                $question->setCategory( $em->getRepository(Category::class)->find($data['newCategory']) );

            $question->setRang($rang);
            $em->persist($question);
        }
        $em->flush();

        return new JsonResponse(true);
    }
}

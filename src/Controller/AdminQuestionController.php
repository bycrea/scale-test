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
     * @Route("/new/{diagnosticId}/{category}", name="new", methods={"GET", "POST"})
     * @throws Exception
     */
    public function newQuestion($diagnosticId, Category $category, Request $request, EntityManagerInterface $em): Response
    {
        return $this->render('admin/question_edit.html.twig', [
            'active'       => 'diagnostics',
            'diagnosticId' => $diagnosticId,
            'category'     => $category,
            'answerTypes'  => Question::ANSWERTYPES
        ]);
    }


    /**
     * @Route("/edit/{diagnosticId}/{category}/{question}", name="edit", defaults={"question": "null"}, methods={"GET", "POST"})
     */
    public function editQuestion($diagnosticId, Category $category, ?Question $question, Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {
            try {
                $r = $request->request;

                $question = $question ?? new Question();
                $question
                    ->setLastUpdate(new \DateTime())
                    ->setAsk($r->get('ask'))
                    ->setHelper($r->get('helper'))
                    ->setRequired($r->get('required', false) == 'true')
                    ->setAnswerType($r->get('answerType'))
                    ->setCategory($category)
                    ->setCategoryFactor($r->get('categoryFactor', 0))
                    ->setGlobalFactor($r->get('globalFactor', 0))
                    ->setQlink($r->get('qlink', []))
                    ->setQnext($r->get('qnext', []))
                    ->setRang($question->getRang() ?? $em->getRepository(Question::class)->count([]) + 1);

                $answers = [];
                $scores  = $r->get('scores', []);
                foreach ($r->get('answers', []) as $key => $answer) $answers[$key] = ['answer' => $answer, 'score' => $scores[$key]];
                $question->setAnswers($answers);

                $em->persist($question);
                $em->flush();
            } catch (Exception $e) {
                throw new Exception($e);
            }

            return $this->redirectToRoute('admin_diagnostic_edit', ['diagnostic' => $diagnosticId]);
        }

        $response   = $this->forward('App\Controller\AdminAnswerController::editAnswerType', ['question'  => $question->getId()]);
        $answerHTML = $response->getContent();

        return $this->render('admin/question_edit.html.twig', [
            'active'       => 'diagnostics',
            'diagnosticId' => $diagnosticId,
            'category'     => $question->getCategory(),
            'answerTypes'  => Question::ANSWERTYPES,
            'question'     => $question,
            'answerHTML'   => $answerHTML
        ]);
    }

    /**
     * @Route("/delete/{question}", name="delete", methods={"GET", "POST"})
     */
    public function deleteQuestion(Question $question, Request $request, EntityManagerInterface $em): Response
    {
        if($request->getMethod() === "POST") {

            // NOTE delete question
            $em->remove($question);
            $em->flush();

            // NOTE reOrder category's questions rang
            $questions = $em->getRepository(Question::class)->findBy(['category' => $question->getCategory()], ['rang' => 'ASC']);
            for ($i=0; $i<count($questions); $i++) {
                $questions[$i]->setRang($i + 1);
                $em->persist($questions[$i]);
            }

            $em->flush();

            return $this->redirect($request->request->get('referrer'));
        }

        $diagnostics  = $em->getRepository(Diagnostic::class)->findAll();
        $countQinDiag = 0;
        $countQinPart = 0;

        foreach ($diagnostics as $diagnostic) {
            if (in_array($question->getId(), $diagnostic->getQuestions())) {
                $countQinDiag += 1;
                $countQinPart += $diagnostic->getParticipations()->count();
            }
        }

        return $this->render('admin/question_delete.html.twig', [
            'active'         => 'diagnostics',
            'question'       => $question,
            'diagnostics'    => $countQinDiag,
            'participations' => $countQinPart,
            'referrer'       => $request->server->get('HTTP_REFERER')
        ]);
    }

    /**
     * @Route("/move", name="move", methods={"PATCH"})
     * @throws Exception
     */
    public function moveQuestion(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $id => $rang)
        {
            $question = $em->getRepository(Question::class)->find($id);
            if($question === null) throw new Exception("Wrong param id : $id", 400);

            $question->setRang($rang);
            $em->persist($question);
        }
        $em->flush();

        return new JsonResponse(true);
    }
}

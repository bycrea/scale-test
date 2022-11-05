<?php

namespace App\Controller;

use App\Entity\Diagnostic;
use App\Entity\Question;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/answer", name="admin_anwser_")
 */
class AdminAnswerController extends AbstractController
{
    /**
     * @Route("/create/{diagnostic}/{id}", name="create", defaults={"id": null})
     */
    public function createAnswerType(Diagnostic $diagnostic, ?string $id): Response
    {
        if(NULL === $id) return new Response("", 200);

        return $this->render('admin/answer_create.html.twig', [
            'diagnostic' => $diagnostic,
            'id'         => $id,
            'answerType' => Question::ANSWERTYPES[$id]
        ]);
    }

    /**
     * @Route("/edit/{question}", name="edit")
     */
    public function editAnswerType(Question $question): Response
    {
        return $this->render('admin/answer_edit.html.twig', [
            'diagnostic' => $question->getDiagnostic(),
            'id'         => $question->getAnswerType(),
            'answerType' => Question::ANSWERTYPES[$question->getAnswerType()],
            'question'   => $question
        ]);
    }
}

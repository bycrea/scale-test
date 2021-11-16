<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/answer", name="admin_anwser_")
 */
class AdminAnswerController extends AbstractController
{
    /**
     * @Route("/create/{id}", name="create", defaults={"id": null})
     */
    public function createAnswerType(?string $id): Response
    {
        if(NULL === $id) return new Response("", 200);

        return $this->render('admin/answer_create.html.twig', [
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
            'id'         => $question->getAnswerType(),
            'answerType' => Question::ANSWERTYPES[$question->getAnswerType()],
            'question'   => $question
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Participation;
use App\Entity\Question;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/participation", name="participation_")
 */
class ParticipationController extends AbstractController
{
    /** @var EntityManagerInterface  */
    private $em;

    /** @var ScoringService */
    private $scoringService;

    public function __construct(EntityManagerInterface $em, ScoringService $scoringService)
    {
        $this->em = $em;
        $this->scoringService = $scoringService;
    }

    /**
     * @Route("/start/{participation}", name="start", methods={"GET"})
     */
    public function startParticipation(Participation $participation, EntityManagerInterface $em): Response
    {
        if($participation->getUser() !== $this->getUser() || $participation->getDone())
            return $this->redirectToRoute('index');

        $diagnostic = $participation->getDiagnostic();

        return $this->render('participation/start.html.twig', [
            'participation' => $participation,
            'diagnostic'    => $diagnostic,
            'initiate'      => $participation->getMeta()['initiate'] ?? false
        ]);
    }


    /**
     * @Route("/next/{participation}", name="next", methods={"GET", "POST"})
     */
    public function nextQuestion(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        $error = false;
        if($participation->getUser() !== $this->getUser())
            $error = true;


        // NOTE Save answer & meta
        if($request->getMethod() === "POST") {
            $actualQuestion = $em->getRepository(Question::class)->find($request->request->get('question'));
            $participation->setAnswers($this->updateAnswers($request->request, $actualQuestion, $participation->getAnswers()));

            // NOTE nextQuestion
            $participation = $this->getNextQuestion($participation, $actualQuestion);

            // NOTE Update meta
            if(!isset($participation->getMeta()['start'])) $participation->updateMeta('start', (new \DateTime())->getTimestamp());
            $participation->updateMeta('time', (new \DateTime())->setTimestamp($participation->getMeta()['start'])->diff(new \DateTime()));

            $participation->setLastUpdate(new \DateTime());

            $em->persist($participation);
            $em->flush();
        }

        $nextQuestions = $em->getRepository(Question::class)->getNextQuestion($participation->getDiagnostic(), $actualQuestion ?? null);
        $nextQuestion  = $participation->getMeta()['initiate'] ?? (!empty($nextQuestions) ? $nextQuestions[0] : null);

        if($nextQuestion === false) {
            $participation->setDone(true);

            $em->persist($participation);
            $em->flush();
        } else {
            $participation->updateMeta('total', $em->getRepository(Question::class)->countQuestions($participation->getDiagnostic()));
            $participation->updateMeta('left', count($nextQuestions));

            // NOTE build next question
            $html = $this->renderView('participation/question_create.html.twig', [
                'participation' => $participation,
                'question'      => $em->getRepository(Question::class)->find($nextQuestion)
            ]);
        }

        return new JsonResponse([
            'html'  => $html ?? "",
            'done'  => $participation->getDone()
        ], $error ? 400 : 200);
    }


    /**
     * @Route("/over-view/{participation}", name="over-view", methods={"GET"})
     */
    public function overParticipation(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        if($participation->getUser() !== $this->getUser())
            return $this->redirectToRoute('index');

        if($participation->getDone() === true) {
            $this->scoringService->getResults($participation);

            $interval = $participation->getMeta()['time'];
            $participation->updateMeta('time', $interval->d == 0 ? $interval->format('%Hh%im%ss') : $interval->format('%dJours, %Hh%im%ss'));
        }

        return $this->render('participation/over-view.html.twig', [
            'participation' => $participation,
            'categories'    => $em->getRepository(Category::class)->findBy([], ['rang' => 'ASC'])
        ]);
    }


    /**
     * @throws Exception
     */
    private function updateAnswers(InputBag $inputs, Question $question, array $answers): array
    {
        foreach ($inputs->get('answers') as $answer) {
            if($question->getAnswerType() == 12 || $question->getAnswerType() == 13)
                $answer = ( new \DateTime($answer))->format(Question::ANSWERTYPES[$question->getAnswerType()]['method']);

            $answers[$question->getId()][] = $answer;
        }

        return $answers;
    }


    private function getNextQuestion(Participation $participation, Question $question): Participation
    {
        // NOTE Call QNext
        if(!empty($question->getQnext()) && $this->scoringService->getQNextValidation($question, $participation) !== false)
        {
            // NOTE set pending as initiate
            if(!isset($participation->getMeta()['pending']))
                $participation->updateMeta('pending', $participation->getMeta()['initiate'] ?? $participation->getDiagnostic()->getQuestions()[0]);

            $participation->updateMeta('initiate', $question->getQnext()[0]);
            return $participation;
        }
        // NOTE OR call pending (question QNext originator)
        else if(isset($participation->getMeta()['pending']))
        {
            $question = $this->em->getRepository(Question::class)->find($participation->getMeta()['pending']);
            $participation->unsetMeta('pending');
        }

        $nextQuestions = $this->em->getRepository(Question::class)->getNextQuestion($question->getDiagnostic(), $question);
        $participation->updateMeta('initiate', !empty($nextQuestions) ? $nextQuestions[0]->getId() : false);

        return $participation;
    }
}

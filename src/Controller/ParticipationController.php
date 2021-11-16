<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Entity\Question;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
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

        $questions = $participation->getDiagnostic()->getQuestions();

        // NOTE Save answer & meta
        if($request->getMethod() === "POST") {
            $actualQuestion = $em->getRepository(Question::class)->find($request->request->get('question'));
            $participation->setAnswers($this->updateAnswers($request->request, $actualQuestion, $participation->getAnswers()));

            $participation->setMeta($this->updateMeta($participation, $actualQuestion, $questions));
            $participation->setLastUpdate(new \DateTime());

            $em->persist($participation);
            $em->flush();
        }

        $nextQuestion = $participation->getMeta()['initiate'] ?? $questions[0];
        $nextQuestion = $em->getRepository(Question::class)->find($nextQuestion);

        if($nextQuestion === null) {
            $participation->setDone(true);
            $em->persist($participation);
            $em->flush();
        } else {
            if(!isset($participation->getMeta()['total']))
                $participation->setMeta(['total' => count($questions), 'left' => count($questions)]);

            // NOTE build next question
            $html = $this->renderView('participation/question_create.html.twig', [
                'participation' => $participation,
                'question'      => $nextQuestion
            ]);
        }

        return new JsonResponse([
            'html'  => $html ?? "",
            'done'  => $participation->getDone()
        ], $error ? 400 : 200);
    }


    /**
     * @Route("/over/{participation}", name="over", methods={"GET"})
     */
    public function overParticipation(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        foreach ($participation->getAnswers() as $q => $answer)
            dump($this->scoringService->getAnswerScore($em->getRepository(Question::class)->find($q), $participation));

        dd($participation->getMeta());

        if($participation->getUser() !== $this->getUser())
            return $this->redirectToRoute('index');

        // TODO diagnostic is over
    }


    private function updateAnswers(InputBag $inputs, Question $question, array $answers): array
    {
        foreach ($inputs->get('answers') as $answer) {
            $answers[$question->getId()][] = $answer;
        }

        return $answers;
    }

    private function updateMeta(Participation $participation, Question $question, array $questions): array
    {
        $meta = $participation->getMeta();

        // TODO remove
        $meta['lastScore'] = $this->scoringService->getAnswerScore($question, $participation);

        // NOTE Get total
        if(!isset($meta['total'])) $meta['total'] = count($questions);

        // NOTE nextQuestion question
        $this->getNextQuestion($participation, $question, $questions, $meta);

        // NOTE Get questions left
        $meta['left'] = count($questions);

        // NOTE time spend
        if(!isset($meta['start'])) $meta['start'] = (new \DateTime())->getTimestamp();
        $meta['time'] = (new \DateTime())->setTimestamp($meta['start'])->diff(new \DateTime());

        return $meta;
    }

    private function getNextQuestion(Participation $participation, Question $question, array &$questions, array &$meta): void
    {
        // NOTE Call QNext
        if(!empty($question->getQnext()) && $this->scoringService->getQNextValidation($question, $participation) !== false)
        {
            // NOTE set pending as initiate
            if(!isset($participation->getMeta()['pending']))
                $meta['pending'] = $meta['initiate'];

            $meta['initiate'] = $question->getQnext()[0];
            return;
        }
        // NOTE OR call pending (question QNext originator)
        else if(isset($participation->getMeta()['pending']))
        {
            $question = $this->em->getRepository(Question::class)->find($participation->getMeta()['pending']);
            unset($meta['pending']);
        }

        // NOTE Get next question after $question in $questions array
        foreach ($questions as $key => $q) {
            unset($questions[$key]);
            if($q === $question->getId()) {
                $meta['initiate'] = $questions[$key + 1] ?? false;
                return;
            }
        }

        $meta['initiate'] = false;
    }
}

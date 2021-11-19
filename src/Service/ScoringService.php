<?php

namespace App\Service;

use App\Entity\Participation;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;

class ScoringService
{
    /*** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function getScoresUser(Participation $participation)
    {
        $questions = $participation->getDiagnostic()->getQuestions();

        foreach ($questions as $question)
        {
            if($question->getActivated() === false) continue;

            // NOTE Get result
            $question->results = [
                'score'  => $this->getAnswerScore($question, $participation),
                'answer' => $participation->getAnswers()[$question->getId()] ?? null
            ];

            // NOTE Activate QNext
            if(!empty($QNext = $question->getQnext()))
            {
                $next = $this->em->getRepository(Question::class)->find($QNext[0]);
                $next->setActivated(true);

                $question->results = [
                    'score'  => $this->getAnswerScore($question, $participation),
                    'answer' => $participation->getAnswers()[$question->getId()] ?? null
                ];
            }
        }
    }


    public function getAnswerScore(Question $question, Participation $participation): int
    {
        $answers = $participation->getAnswers()[$question->getId()] ?? null;
        $method  = Question::ANSWERTYPES[$question->getAnswerType()]['method'];
        $result  = 0;

        if($question->getAnswerType() < 10 && $answers !== null)
        {
            switch ($method) {
                case "+":
                    foreach ($answers as $answer) $result += $question->getAnswers()[$answer]['score'];
                    break;
                case "|":
                    foreach ($answers as $answer) $result = $result | $question->getAnswers()[$answer]['score'];
                    break;
                case "&":
                    $result = 1;
                    foreach ($answers as $answer) $result = $result & $question->getAnswers()[$answer]['score'];
                    break;
                case "*":
                    $result = count($answers) == count($question->getAnswers());
                    break;
                default:
                    foreach ($answers as $answer) $result = $question->getAnswers()[$answer]['score'];
                    break;
            }
        }

        // NOTE QLink
        if(!empty($QLink = $question->getQlink()))
        {
            $QLinkResult = $this->getAnswerScore($this->em->getRepository(Question::class)->find($QLink[0]), $participation);

            switch ($QLink[1]) {
                case ">":
                    $result = $QLinkResult > $result ? ($QLink[2] != "" ? $QLink[2] : $result) : 0;
                    break;
                case "<":
                    $result = $QLinkResult < $result ? ($QLink[2] != "" ? $QLink[2] : $result) : 0;
                    break;
                case ">=":
                    $result = $QLinkResult >= $result ? ($QLink[2] != "" ? $QLink[2] : $result) : 0;
                    break;
                case "<=":
                    $result = $QLinkResult <= $result ? ($QLink[2] != "" ? $QLink[2] : $result) : 0;
                    break;
                case "==":
                    $result = $QLinkResult == $result ? ($QLink[2] != "" ? $QLink[2] : $result) : 0;
                    break;
                default:
                    break;
            }
        }

        return $result;
    }


    public function getQNextValidation(Question $question, Participation $participation): bool
    {
        $score     = $this->getAnswerScore($question, $participation);
        $condition = $question->getQnext()[1];
        $value     = $question->getQnext()[2];

        switch ($condition) {
            case ">": return $score > $value;
            case "<": return $score < $value;
            case ">=": return $score >= $value;
            case "<=": return $score <= $value;
            case "==": return $score == $value;
            default:  return false;
        }
    }
}
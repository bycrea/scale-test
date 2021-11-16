<?php

namespace App\Service;

use App\Entity\Participation;
use App\Entity\Question;

class ScoringService
{
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
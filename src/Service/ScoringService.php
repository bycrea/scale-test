<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Diagnostic;
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


    public function getResults(Participation $participation, bool $isAdmin = false)
    {
        $questions = $participation->getDiagnostic()->getQuestions();

        foreach ($questions as $question)
        {
            if($question->getActivated() === false) continue;

            // NOTE Get result
            $question->results = [
                'score'     => $this->getAnswerScore($question, $participation),
                'scoreMax'  => $this->getAnswerMaxScore($question, $participation),
                'answer'    => $participation->getAnswers()[$question->getId()] ?? null
            ];

            // NOTE Activate QNext
            if(!empty($QNext = $question->getQnext()))
            {
                $next = $this->em->getRepository(Question::class)->find($QNext[0]);
                $next->setActivated(true);

                $next->results = [
                    'score'     => $this->getAnswerScore($question, $participation),
                    'scoreMax'  => $this->getAnswerMaxScore($question, $participation),
                    'answer'    => $participation->getAnswers()[$question->getId()] ?? null
                ];
            }
        }

        $this->getCategoriesResults($participation, $isAdmin);
        $this->getGlobalResults($participation, $isAdmin);
    }


    public function getCategoriesResults(Participation $participation, bool $isAdmin)
    {
        $categories = $this->em->getRepository(Category::class)->findBy([], ['rang' => 'ASC']);

        foreach ($categories as $category)
        {
            $category->results = ['scores' => 0, 'scoresMax' => 0, 'percentage' => "N.C.", 'maturity' => !$isAdmin && $category->getRang() == 1 ? "" : null];

            foreach ($participation->getDiagnostic()->getQuestions() as $q) {
                // NOTE Get category scores (score * factor)
                if ($q->getCategory() === $category) {
                    $category->results['scores']    += ($q->results['score'] * $q->getCategoryFactor());
                    $category->results['scoresMax'] += ($q->results['scoreMax'] * $q->getCategoryFactor());
                }
            }

            // NOTE Get category maturity
            if($category->results['scores'] == 0 || $category->results['scoresMax'] == 0) {
                if ($isAdmin) $category->results['percentage'] = 0;
                continue;
            }

            $scale  = $category->results['percentage'] = $category->results['scores'] / $category->results['scoresMax'] * 100;
            $scales = $participation->getDiagnostic()->getCategoriesScales()[$category->getId()];
            $stepUp = 0;

            foreach(Diagnostic::CATEGORY_MATURITY as $key => $maturity) {
                if($scale >= $stepUp && $scale <= intval($scales[$key])) {
                    $category->results['maturity'] = $maturity;
                    break;
                } else {
                    $stepUp = intval($scales[$key]);
                }
            }
        }
    }


    public function getGlobalResults(Participation $participation, bool $isAdmin)
    {
        $participation->results = ['scores' => 0, 'scoresMax' => 0, 'percentage' => 'N.C.', 'maturity' => null];

        foreach ($participation->getDiagnostic()->getQuestions() as $q) {
            // NOTE Get global scores (score * factor)
            $participation->results['scores']    += ($q->results['score'] * $q->getGlobalFactor());
            $participation->results['scoresMax'] += ($q->results['scoreMax'] * $q->getGlobalFactor());
        }

        // NOTE Get global maturity
        if($participation->results['scores'] == 0 || $participation->results['scoresMax'] == 0) return;

        $scale = $participation->results['percentage'] = $participation->results['scores'] / $participation->results['scoresMax'] * 100;
        $globals = $participation->getDiagnostic()->getGlobalScale();
        $stepUp  = 0;

        foreach($globals as $labelScale) {
            if($scale >= $stepUp && $scale <= intval($labelScale['scale'])) {
                $participation->results['maturity'] = $labelScale['label'];
                break;
            } else {
                $stepUp = intval($labelScale['scale']);
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
                    $result = count($answers) == count($question->getAnswers()) ? 1 : 0;
                    break;
                default:
                    // NOTE selection unique
                    $result = $question->getAnswers()[$answers[0]]['score'];
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


    public function getAnswerMaxScore(Question $question, Participation $participation): int
    {
        $answers = $participation->getAnswers()[$question->getId()] ?? null;
        $method  = Question::ANSWERTYPES[$question->getAnswerType()]['method'];
        $result  = 0;

        // NOTE unrequired questions need to be answered to count
        if($question->getRequired() === false && $answers === null)
            return 0;

        if($question->getAnswerType() < 10)
        {
            switch ($method) {
                case "+":
                    foreach ($question->getAnswers()['score'] as $score) $result += $score;
                    break;
                case "|":
                case "&":
                case "*":
                    $result = 1;
                    break;
                default:
                    // NOTE selection unique
                    foreach ($question->getAnswers() as $answer) if($answer['score'] > $result) $result = $answer['score'];
                    break;
            }
        }

        // NOTE QLink imposed result
        if(!empty($question->getQlink()) && $question->getQlink()[2] != "") $result = $question->getQlink()[2];

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
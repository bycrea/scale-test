<?php

namespace App\Service;

use App\Entity\Participation;

class CsvService
{
    public static function buildHeader(Participation $participation, array $categories): string
    {
        $line  = "ID";
        $line .= ";Diagnostique";
        $line .= ";Participant";
        $line .= ";Email";
        $line .= ";Entreprise";
        $line .= ";Derr. Modif";
        $line .= ";Temps Passé";
        $line .= ";Mat. Global";
        $line .= ";Note (%)";

        foreach ($categories as $category)
        {
            $line .= ";Mat. " . $category->getName();
            $line .= ";Note (%)";
        }

        foreach ($categories as $category)
        {
            foreach ($participation->getDiagnostic()->getQuestions() as $question)
            {
                if($question->getActivated() === false) continue;
                $line .= ";Q" . $question->getId() . ". " . $question->getAsk();
            }
        }

        return $line;
    }

    public static function concatParticipation(Participation $participation, array $categories): string
    {
        $line  = $participation->getId();
        $line .= ";" . $participation->getDiagnostic()->getName();
        $line .= ";" . $participation->getUser()->getUsername();
        $line .= ";" . $participation->getUser()->getEmail();
        $line .= ";" . $participation->getUser()->getCompany();
        $line .= ";" . $participation->getLastUpdate()->format('d/m/Y H:i');
        $line .= ";" . $participation->getMeta()['time'];
        $line .= ";" . $participation->results['maturity'];
        $line .= ";" . ($participation->results['percentage'] == 100 ? $participation->results['percentage'] : substr($participation->results['percentage'], 0, 4));

        foreach ($categories as $category)
        {
            $line .= ";" . $category->results['maturity'];
            $percentage = $category->results['percentage'] == 100 ? $category->results['percentage'] : substr($category->results['percentage'], 0, 4);
            $line .= ";" . str_replace(".", ",", $percentage);
        }

        foreach ($categories as $category)
        {
            foreach ($participation->getDiagnostic()->getQuestions() as $question)
            {
                if($question->getActivated() === false) continue;

                $line .= ";";
                $answers = $participation->getAnswers()[$question->getId()] ?? [];
                foreach ($participation->getAnswers()[$question->getId()] ?? [] as $key => $answer)
                {
                    $line .= $question->getAnswers()[$answer]['answer'];
                    if($key < count($answers) -1) $line .= ",";
                }
            }
        }

        return $line;
    }
}
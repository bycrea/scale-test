<?php

namespace App\Controller;

use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $participations = $em->getRepository(Participation::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('index/index.html.twig', [
            'participations' => $participations,
        ]);
    }


    /**
     * @Route("/ban", name="ban")
     */
    public function ban(): Response
    {
        return $this->render('index/ban.html.twig', [
            'controller_name' => 'Ban',
        ]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => 'Index',
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

<?php

namespace App\Controller;

use App\Entity\Diagnostic;
use App\Entity\Participation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->redirectToRoute('admin_users');
    }


    /**
     * @Route("/users", name="users")
     */
    public function users(EntityManagerInterface $em): Response
    {
        return $this->render('admin/users.html.twig', [
            'active' => 'users',
            'users'  => $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC'])
        ]);
    }


    /**
     * @Route("/diagnostics", name="diagnostics")
     */
    public function diagnostics(EntityManagerInterface $em): Response
    {
        return $this->render('admin/diagnostics.html.twig', [
            'active'      => 'diagnostics',
            'diagnostics' => $em->getRepository(Diagnostic::class)->findBy([], ['lastUpdate' => 'DESC'])
        ]);
    }


    /**
     * @Route("/participations", name="participations")
     */
    public function participations(EntityManagerInterface $em): Response
    {
        return $this->render('admin/participations.html.twig', [
            'active'         => 'participations',
            'participations' => $em->getRepository(Participation::class)->findBy([], ['lastUpdate' => 'DESC'])
        ]);
    }
}

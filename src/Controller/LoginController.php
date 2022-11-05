<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function index(AuthenticationUtils $authenticationUtils, Request $request, EntityManagerInterface $em): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if($user = $this->getUser())
        {
            $user->setLastConnection(new \DateTime());
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('index');
        }

         return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
             'error'        => $error,
             'link'         => [
                 'email'    => $request->get('u', ""),
                 'password' => $request->get('p', "")
             ]
        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     * @throws Exception
     */
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }
}

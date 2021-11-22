<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/user", name="admin_user_")
 */
class AdminUserController extends AbstractController
{

    /**
     * @Route("/edit/{id}", name="edit", defaults={"id": null}, methods={"GET", "POST"})
     * @throws Exception
     */
    public function editUser(?string $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $error = false;
        $user  = $em->getRepository(User::class)->findOneBy(['id'=>$id]) ??  new User();

        if($request->getMethod() === "POST")
        {
            try {
                // NOTE new user
                if($id === null) {
                    $hash = $passwordHasher->hashPassword($user, $user->createdAccessPassword());
                    $user->setPassword($hash);
                }

                $inputs = ['username'=>'*', 'email'=>'*', 'company'=>''];
                foreach ($inputs as $input => $required) {
                    if($required == '*' && empty($request->request->get($input))) {
                        $error = true;
                        break;
                    } else {
                        $user->{'set' . ucfirst($input)}($request->request->get($input));
                    }
                }

                if(!$error) {
                    $em->persist($user);
                    $em->flush();
                    return $this->redirectToRoute('admin_users');
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }

        return $this->render('admin/user_edit.html.twig', [
            'active' => 'users',
            'user'   => $user
        ]);
    }

    /**
     * @Route("/toggle/activate/{id}", name="toggle_activate", methods={"GET"})
     * @throws Exception
     */
    public function toggleActivateUser(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        $user->setActivated( !$user->getActivated() );

        $em->persist($user);
        $em->flush();

        return new JsonResponse(true);
    }

    /**
     * @Route("/delete/{id}", name="delete", methods={"GET"})
     * @throws Exception
     */
    public function deleteUser(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_users');
    }
}

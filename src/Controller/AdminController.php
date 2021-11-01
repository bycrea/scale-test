<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        $users = $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/users.html.twig', [
            'active' => 'users',
            'users'  => $users
        ]);
    }

    /**
     * @Route("/user/edit/{id}", name="edit_user", defaults={"id": null}, methods={"GET", "POST"})
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
     * @Route("/user/delete/{id}", name="delete_user", methods={"GET"})
     * @throws Exception
     */
    public function deleteUser(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_users');
    }


    /**
     * @Route("/diagnostics", name="diagnostics")
     */
    public function diagnostics(): Response
    {
        return $this->render('admin/diagnostics.html.twig', [
            'active' => 'diagnostics',
        ]);
    }



    /**
     * @Route("/participations", name="participations")
     */
    public function participations(): Response
    {
        return $this->render('admin/participations.html.twig', [
            'active' => 'participations',
        ]);
    }
}

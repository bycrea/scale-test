<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
     * @throws TransportExceptionInterface
     */
    public function editUser(?string $id, Request $request, MailerService $mailerService,
            EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $error = false;
        $user  = $em->getRepository(User::class)->findOneBy(['id'=>$id]) ??  new User();

        if($request->getMethod() === "POST")
        {
            try {
                $inputs = ['username'=>'*', 'email'=>'*', 'company'=>''];
                foreach ($inputs as $input => $required) {
                    if($required == '*' && empty($request->request->get($input))) {
                        $error = true;
                        break;
                    } else {
                        $user->{'set' . ucfirst($input)}($request->request->get($input));
                    }
                }

                if($request->request->get('admin') === 'on')
                    $user->setRoles(['ROLE_ADMIN']);
                else
                    $user->setRoles(['ROLE_USER']);

                if(!$error) {

                    // NOTE new user
                    if($id === null) {
                        $hash = $passwordHasher->hashPassword($user, $user->createdAccessPassword());
                        $user->setPassword($hash);
                        $mailerService->sendLoginMailer($user);
                    }

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
     * @Route("/send-link/{id}", name="send-link", methods={"GET"})
     * @throws Exception
     */
    public function sendLink(string $id, EntityManagerInterface $em, MailerService $mailerService): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if($user->getActivated()) {
            $mailerService->sendLoginMailer($user);
            return new JsonResponse(true);
        } else {
            return new JsonResponse(false);
        }
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

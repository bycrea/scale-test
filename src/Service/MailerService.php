<?php

namespace App\Service;

use App\Entity\User;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    /** @var MailerInterface  */
    private $mailer;

    /** @var RequestStack  */
    private $request;

    /** @var string */
    private $sender;

    public function __construct(MailerInterface $mailer, RequestStack $requestStack, string $sender)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->mailer  = $mailer;
        $this->sender  = $sender;
    }

    /**
     * @throws Exception
     */
    public function sendLoginMailer(User $user)
    {
        $loginUrl   = $this->request->server->get('HTTP_HOST') . '/login';
        $loginUrl  .= "?u=".$user->getEmail()."&p=".$user->getAccessPassword();

        $params = ['name' => $user->getUsername(), 'link' => $loginUrl];
        $email  = new Email();

        try {
            // NOTE set headers
            $email
                ->getHeaders()
                ->addTextHeader('templateId', 5)
                ->addParameterizedHeader('params', 'params', $params)
            ;

            // NOTE set basics
            $email
                ->addTo($user->getEmail())
                ->from($this->sender)
                ->html("")
            ;

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new Exception($e);
        }
    }
}
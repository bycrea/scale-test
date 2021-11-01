<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Security;

class RequestAuthorizer
{
    /*** @var TokenStorage */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if($this->security->getUser() !== null) {
            if($this->security->getUser()->getActivated() === false && $event->getRequest()->getRequestUri() !== "/ban")
                $event->setResponse(new RedirectResponse('/ban', 302));
        }
    }
}
<?php

namespace App\Listener;

use App\Entity\User;
use App\Security\AppAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ControllerListener implements EventSubscriberInterface
{

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    )
    {}

    public static function getSubscribedEvents()
    {
       return [
           KernelEvents::CONTROLLER => 'onControllerEvent'
       ];
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        if (!$event->getController()) {
            return;
        }
        $request = $event->getRequest();
        $route = $request->attributes->get("_route");
        if ($route == AppAuthenticator::INDEX_ROUTE) {
            return;
        }
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return;
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $redirect = false;

        if ($user && !$user->isVerified()) {
           $redirect = true;
        }
        else if (!in_array($route, AppAuthenticator::ALLOW_ROUTES_FOR_NOT_LOGGED_IN)) {
            $redirect = true;
        }

        if ($redirect) {
            $url = $this->urlGenerator->generate(AppAuthenticator::INDEX_ROUTE);
            $response = new RedirectResponse($url);

            $event->setController(function () use ($response) {
                return $response;
            });
        }
    }
}
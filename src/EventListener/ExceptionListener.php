<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Vérifie si l'exception est une erreur 404
        if ($exception instanceof NotFoundHttpException) {
            // Redirige vers la route 'error_404' qui gère les erreurs 404
            $response = new RedirectResponse($this->router->generate('error_404'));
            $event->setResponse($response);
        }
    }
}

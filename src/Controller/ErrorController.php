<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function showException(\Throwable $exception): Response
    {
        if ($exception instanceof NotFoundHttpException) {
            return $this->render('pages/error404.html.twig', [
                'message' => 'La page demandée est introuvable.',
            ]);
        }

        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        return $this->render('pages/error.html.twig', [
            'message' => 'Une erreur est survenue, retour à l\'accueil.',
            'status_code' => $statusCode,
        ]);
    }
}
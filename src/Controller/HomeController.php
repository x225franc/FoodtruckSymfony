<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    #[Route('/error404', name: 'error404')]
    public function notFound(): Response
    {
        return $this->render('pages/error404.html.twig', [
            'message' => 'La page demandée est introuvable.',
        ]);
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {

        return $this->render('pages/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('pages/profile.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Menu;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $menus = $entityManager->getRepository(Menu::class)->findAll();
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->render('pages/index.html.twig', [
            'categories' => $categories,
            'menus' => $menus,
            'products' => $products,
        ]);
    }

    #[Route('/category-details/{id}', name: 'category_details')]
    public function categoryDetails(int $id, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        $menus = $entityManager->getRepository(Menu::class)->findBy(['category' => $category]);
        $products = $entityManager->getRepository(Product::class)->findBy(['category' => $category]);

        return $this->render('pages/category_details.html.twig', [
            'category' => $category,
            'menus' => $menus,
            'products' => $products,
        ]);
    }

    #[Route('/menu-details/{id}', name: 'menu_details')]
    public function menuDetails(int $id, EntityManagerInterface $entityManager): Response
    {
        $menu = $entityManager->getRepository(Menu::class)->find($id);
        $products = $menu->getProducts();

        $menuData = [
            'id' => $menu->getId(),
            'name' => $menu->getName(),
            'description' => $menu->getDescription(),
            'products' => []
        ];

        foreach ($products as $product) {
            $menuData['products'][] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice()
            ];
        }

        return $this->json($menuData);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('pages/profile.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }
}

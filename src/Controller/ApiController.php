<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\PicsumService;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    private $picsumService;

    public function __construct(PicsumService $picsumService)
    {
        $this->picsumService = $picsumService;
    }

    #[Route('/api/products/{id}', name: 'api_product_details', methods: ['GET'])]
    public function getProductDetails(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], 404);
        }

        return $this->json([
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
        ]);
    }

    #[Route('/api/picsum-images', name: 'api_picsum_images', methods: ['GET'])]
    public function getPicsumImages(): JsonResponse
    {
        $imageUrls = $this->picsumService->fetchPicsumImages();
        return $this->json($imageUrls);
    }

    #[Route('/api/products/{id}/reviews', name: 'api_product_reviews', methods: ['GET'])]
    public function getProductReviews(int $id, ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findBy(['product' => $id]);

        $data = [];
        foreach ($reviews as $review) {
            $data[] = [
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'createdAt' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => [
                    'username' => $review->getUser()->getUsername(),
                ],
            ];
        }

        return $this->json($data);
    }
}

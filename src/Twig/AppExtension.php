<?php

namespace App\Twig;

use App\Repository\OrderProductRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Entity\User;

class AppExtension extends AbstractExtension
{
    private $security;
    private $orderProductRepository;
    private $reviewRepository;

    public function __construct(Security $security, OrderProductRepository $orderProductRepository, ReviewRepository $reviewRepository)
    {
        $this->security = $security;
        $this->orderProductRepository = $orderProductRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('reviewableItemsCount', [$this, 'getReviewableItemsCount']),
        ];
    }

    /**
     * @return int
     */
    public function getReviewableItemsCount(): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return 0;
        }

        $finishedOrders = $user->getOrders()->filter(function ($order) {
            return $order->getStatus() === 'finished';
        });

        $reviewableItemsCount = 0;

        foreach ($finishedOrders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getProduct();
                $existingReview = $this->reviewRepository->findOneBy(['product' => $product, 'user' => $user]);

                if (!$existingReview) {
                    $reviewableItemsCount++;
                }
            }
        }

        return $reviewableItemsCount;
    }
}
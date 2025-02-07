<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\ContactMessage;
use App\Entity\Review;
use App\Entity\Address;
use App\Entity\Shipping;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use App\Service\PicsumService;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    private $picsumService;



    public function __construct(UserPasswordHasherInterface $passwordHasher, PicsumService $picsumService)
    {
        $this->passwordHasher = $passwordHasher;
        $this->picsumService = $picsumService;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $imageUrls = $this->picsumService->fetchPicsumImages();

        $roles = ['ROLE_USER', 'ROLE_BANNED', 'ROLE_ADMIN'];

        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->email)
                ->setUsername(str_replace('.', '', $faker->userName))
                ->setFirstname($faker->firstName)
                ->setLastname($faker->lastName)
                ->setPhone($faker->numerify('06########'))
                ->setCreatedat($faker->dateTimeThisDecade)
                ->setPassword($this->passwordHasher->hashPassword($user, '123'))
                ->setRoles([$roles[array_rand($roles)]]);

            $manager->persist($user);
            $users[] = $user;
        }

        $specialUsers = [
            ['email' => 'admin@admin.fr', 'role' => 'ROLE_ADMIN'],
            ['email' => 'user@user.fr', 'role' => 'ROLE_USER'],
            ['email' => 'banned@banned.fr', 'role' => 'ROLE_BANNED'],
        ];

        foreach ($specialUsers as $specialUser) {
            $user = new User();
            $user->setEmail($specialUser['email'])
                ->setUsername(explode('@', $specialUser['email'])[0])
                ->setFirstname($faker->firstName)
                ->setLastname($faker->lastName)
                ->setPhone($faker->numerify('06########'))
                ->setCreatedat($faker->dateTimeThisDecade)
                ->setPassword($this->passwordHasher->hashPassword($user, '123'))
                ->setRoles([$specialUser['role']]);

            $manager->persist($user);
            $users[] = $user;
        }

        $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $category = new Category();
            $category->setTitle($faker->word)
                ->setDescription($faker->sentence)
                ->setImage($imageUrls[array_rand($imageUrls)]);

            $manager->persist($category);
            $categories[] = $category;
        }

        $products = [];
        foreach ($categories as $category) {
            for ($i = 0; $i < 10; $i++) {
                $product = new Product();
                $product->setTitle($faker->word)
                    ->setDescription($faker->sentence)
                    ->setPrice($faker->randomFloat(2, 1, 100))
                    ->setImage($imageUrls[array_rand($imageUrls)])
                    ->setCategory($category);

                $manager->persist($product);
                $products[] = $product;
            }
        }

        $menus = [];
        foreach ($categories as $category) {
            for ($i = 0; $i < 5; $i++) {
                $menu = new Menu();
                $menu->setName($faker->word)
                    ->setDescription($faker->sentence)
                    ->setCategory($category)
                    ->setImage($imageUrls[array_rand($imageUrls)]);

                for ($j = 0; $j < 3; $j++) {
                    $menu->addProduct($products[array_rand($products)]);
                }

                $manager->persist($menu);
                $menus[] = $menu;
            }
        }

        $addresses = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 2; $i++) {
                $address = new Address();
                $address->setStreet($faker->streetAddress)
                    ->setCity($faker->city)
                    ->setState($faker->state)
                    ->setPostalCode($faker->postcode)
                    ->setCountry($faker->country)
                    ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeThisDecade))
                    ->setUser($user);

                $manager->persist($address);
                $addresses[] = $address;
            }
        }

        $orders = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $order = new Order();
                $order->setOrderNumber(uniqid("N°"))
                    ->setTotalPrice($faker->randomFloat(2, 20, 500))
                    ->setPaymentMethod('paiement_sur_place')
                    ->setStatus('finished')
                    ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeThisDecade))
                    ->setUpdatedAt($faker->dateTimeThisDecade)
                    ->setUser($user)
                    ->setAddress($addresses[array_rand($addresses)]);

                $manager->persist($order);
                $orders[] = $order;
            }
        }

        foreach ($orders as $order) {
            for ($i = 0; $i < 5; $i++) {
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order)
                    ->setProduct($products[array_rand($products)])
                    ->setQuantity($faker->numberBetween(1, 10))
                    ->setPrice($faker->randomFloat(2, 1, 100));

                $manager->persist($orderProduct);
            }
        }

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $review = new Review();
                $review->setRating($faker->numberBetween(1, 5))
                    ->setComment($faker->sentence)
                    ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeThisDecade))
                    ->setProduct($products[array_rand($products)])
                    ->setUser($user);

                $manager->persist($review);
            }
        }

        $withdrawalOptions = ['demain', 'demain_plus_un', 'demain_plus_deux', 'demain_plus_trois'];

        foreach ($orders as $index => $order) {
            $shipping = new Shipping();
            $shipping->setShippingMethod($faker->word)
                ->setWithdrawal($withdrawalOptions[$index % count($withdrawalOptions)])
                ->setOrder($order);

            $manager->persist($shipping);
        }

        for ($i = 0; $i < 10; $i++) {
            $contactMessage = new ContactMessage();
            $contactMessage->setEmail($faker->email)
                ->setSubject($faker->sentence)
                ->setMessage($faker->paragraph)
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeThisDecade));

            $manager->persist($contactMessage);
        }

        $manager->flush();
    }
}

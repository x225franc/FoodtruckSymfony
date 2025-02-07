<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Menu;
use App\Entity\Product;
use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Shipping;
use App\Entity\User;
use App\Entity\ContactMessage;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use App\Repository\OrderProductRepository;
use App\Repository\ReviewRepository;

class HomeController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $entityManager, OrderProductRepository $orderProductRepository, ReviewRepository $reviewRepository): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $menus = $entityManager->getRepository(Menu::class)->findAll();
        $products = $entityManager->getRepository(Product::class)->findAll();

        $user = $this->getUser();
        $reviewableItemsCount = $user ? $this->getReviewableItemsCount($user, $orderProductRepository, $reviewRepository) : 0;

        return $this->render('pages/index.html.twig', [
            'categories' => $categories,
            'menus' => $menus,
            'products' => $products,
            'reviewableItemsCount' => $reviewableItemsCount,
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            dump($email);

            if (!is_string($email)) {
                throw new \InvalidArgumentException('Email doit être une chaîne de caractères.');
            }

            $contactMessage = new ContactMessage();
            $contactMessage->setEmail($email);
            $contactMessage->setSubject($subject);
            $contactMessage->setMessage($message);
            $contactMessage->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($contactMessage);
            $entityManager->flush();
            $adminUsers = $userRepository->findByRole('ROLE_ADMIN');

            foreach ($adminUsers as $admin) {
                $emailBody = '
    <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
        <table style="width: 100%; max-width: 600px; margin: auto; background: white; border-radius: 8px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);">
            <tr>
                <td style="text-align: center;">
                    <h2 style="color: #333;">Nouvelle notification</h2>
                    <p style="color: #666; font-size: 14px;">Un utilisateur a envoyé un message :</p>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="background: #f8f8f8; padding: 10px; font-weight: bold;">Email</td>
                            <td style="background: #fff; padding: 10px;">' . htmlspecialchars($email) . '</td>
                        </tr>
                        <tr>
                            <td style="background: #f8f8f8; padding: 10px; font-weight: bold;">Sujet</td>
                            <td style="background: #fff; padding: 10px;">' . htmlspecialchars($subject) . '</td>
                        </tr>
                        <tr>
                            <td style="background: #f8f8f8; padding: 10px; font-weight: bold;">Message</td>
                            <td style="background: #fff; padding: 10px;">' . nl2br(htmlspecialchars($message)) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="text-align: center; padding: 20px; color: #888; font-size: 12px;">
                    © ' . date('Y') . ' Burgererie - Tous droits réservés.
                </td>
            </tr>
        </table>
    </div>';

                $emailMessage = (new Email())
                    ->from($email)
                    ->to($admin->getEmail())
                    ->subject("Nouveau message reçu - " . $subject)
                    ->html($emailBody);

                $mailer->send($emailMessage);
            }

            $this->addFlash('success', 'Votre message a été envoyé avec succès.');
            return $this->redirectToRoute('home');
        }

        return $this->render('pages/contact.html.twig', []);
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

    public function getReviewableItemsCount(User $user, OrderProductRepository $orderProductRepository, ReviewRepository $reviewRepository): int
    {
        $finishedOrders = $user->getOrders()->filter(function (Order $order) {
            return $order->getStatus() === 'finished';
        });

        $reviewableItemsCount = 0;

        foreach ($finishedOrders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getProduct();
                $existingReview = $reviewRepository->findOneBy(['product' => $product, 'user' => $user]);

                if (!$existingReview) {
                    $reviewableItemsCount++;
                }
            }
        }

        return $reviewableItemsCount;
    }

    #[Route('/review', name: 'review')]
    public function review(Request $request, EntityManagerInterface $entityManager, OrderProductRepository $orderProductRepository, ReviewRepository $reviewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $productIds = $request->request->all('product_id');
            $ratings = $request->request->all('rating');
            $comments = $request->request->all('comment');

            foreach ($productIds as $index => $productId) {
                $product = $entityManager->getRepository(Product::class)->find($productId);
                $existingReview = $reviewRepository->findOneBy(['product' => $product, 'user' => $user]);

                if ($product && !$existingReview) {
                    $review = new Review();
                    $review->setProduct($product);
                    $review->setUser($user);
                    $review->setRating($ratings[$index]);
                    $review->setComment($comments[$index]);
                    $entityManager->persist($review);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vos avis ont été enregistrés.');
            return $this->redirectToRoute('home');
        }

        $finishedOrders = $user->getOrders()->filter(function (Order $order) {
            return $order->getStatus() === 'finished';
        });

        $reviewableItems = [];

        foreach ($finishedOrders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getProduct();
                $existingReview = $reviewRepository->findOneBy(['product' => $product, 'user' => $user]);

                if (!$existingReview) {
                    $reviewableItems[] = [
                        'product' => $product,
                        'orderProduct' => $orderProduct,
                    ];
                }
            }
        }

        return $this->render('pages/review.html.twig', [
            'reviewableItems' => $reviewableItems,
        ]);
    }


    #[Route('/order', name: 'order')]
    public function order(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $address = $entityManager->getRepository(Address::class)->findOneBy(['user' => $user]);
        $addressData = $address ? [
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'postal_code' => $address->getPostalCode(),
            'country' => $address->getCountry(),
        ] : [];

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $paiement = $request->request->get('paiement');
            $expedition = $request->request->get('expedition');
            $retrait = $request->request->get('retrait');
            $adresse = $request->request->get('adresse');
            $code_postal = $request->request->get('code_postal');
            $ville = $request->request->get('ville');
            $state = $request->request->get('departement');
            $pays = 'France';
            $prix_commande = $request->request->get('prix_commande');

            $cartItems = json_decode($request->request->get('cart'), true);

            $numero_de_commande = uniqid("N°");

            if (!$address) {
                $address = new Address();
                $address->setUser($user);
            }
            $address->setStreet($adresse);
            $address->setCity($ville);
            $address->setState($state);
            $address->setPostalCode($code_postal);
            $address->setCountry($pays);
            $entityManager->persist($address);
            $entityManager->flush();

            $order = new Order();
            $order->setUser($user);
            $order->setAddress($address);
            $order->setOrderNumber($numero_de_commande);
            $order->setTotalPrice($prix_commande);
            $order->setPaymentMethod($paiement);
            $order->setStatus('pending');
            $order->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($order);
            $entityManager->flush();

            if ($cartItems && is_array($cartItems)) {
                foreach ($cartItems as $item) {
                    $orderProduct = new OrderProduct();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($entityManager->getRepository(Product::class)->find($item['id']));
                    $orderProduct->setQuantity($item['quantity']);
                    $orderProduct->setPrice($item['price']);
                    $entityManager->persist($orderProduct);
                }
                $entityManager->flush();
            } else {
                $this->addFlash('error', 'Votre panier est vide ou invalide.');
                return $this->redirectToRoute('home');
            }

            $shipping = new Shipping();
            $shipping->setOrder($order);
            $shipping->setShippingMethod($expedition);
            $shipping->setWithdrawal($retrait);
            $entityManager->persist($shipping);
            $entityManager->flush();

            $emailBody = '
    <div class="row">
        <div class="col-12">
            <table class="body-wrap" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; background-color: transparent; margin: 0;">
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                    <td style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;" valign="top"></td>
                    <td class="container" width="600" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; display: block !important; max-width: 600px !important; clear: both !important; margin: 0 auto;" valign="top">
                        <div class="content" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; max-width: 600px; display: block; margin: 0 auto; padding: 20px;">
                            <table class="main" width="100%" cellpadding="0" cellspacing="0" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; border-radius: 3px;  margin: 0; border: none;">
                                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-wrap aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;padding: 20px; color: #495057; border: 2px solid #1d1e3a;border-radius: 7px; background-color: #fff;" align="center" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                            <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                <td class="content-block" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                                    <h2 class="aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,\'Lucida Grande\',sans-serif; box-sizing: border-box; font-size: 24px; color: #000; line-height: 1.2em; font-weight: 400; text-align: center; margin: 40px 0 0;" align="center">Merci de votre commande !</h2>
                                                </td>
                                            </tr>
                                            <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                <td class="content-block aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top">
                                                    <table class="invoice" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; text-align: left; width: 80%; margin: 40px auto;">
                                                        <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                            <td style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 5px 0;" valign="top"><b>Burgererie</b>
                                                                <br style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;" />Commande Numero #' . $numero_de_commande . '
                                                                <br style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;" />Date souhaitée : ' . date('l d F Y') . '
                                                            </td>
                                                        </tr>
                                                        <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                            <td style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 5px 0;" valign="top">
                                                                <table class="invoice-items" cellpadding="0" cellspacing="0" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; margin: 0;">';

            foreach ($cartItems as $item) {
                $emailBody .= '
            <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; border-top-width: 1px; border-top-color: #eee; border-top-style: solid; margin: 0; padding: 5px 0;" valign="top">' . $item['title'] . ' (x ' . $item['quantity'] . ')</td>
                <td class="alignright" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; text-align: right; border-top-width: 1px; border-top-color: #eee; border-top-style: solid; margin: 0; padding: 5px 0;" align="right" valign="top">' . $item['price'] . ' €</td>
            </tr>';
            }

            $emailBody .= '
            <tr class="total" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                <td class="alignright" width="80%" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; text-align: right; border-top-width: 2px; border-top-color: #333; border-top-style: solid; border-bottom-color: #333; border-bottom-width: 2px; border-bottom-style: solid; font-weight: 700; margin: 0; padding: 5px 0;" align="right" valign="top">Total </td>
                <td class="alignright" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; text-align: right; border-top-width: 2px; border-top-color: #333; border-top-style: solid; border-bottom-color: #333; border-bottom-width: 2px; border-bottom-style: solid; font-weight: 700; margin: 0; padding: 5px 0;" align="right" valign="top">' . $prix_commande . ' €</td>
            </tr>
        </table>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
        <td class="content-block" style="text-align: center;font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0;" valign="top">
            © ' . date('Y') . ' Burgererie
        </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </div>
    </div>';

            $email = (new Email())
                ->from('serviceclientdealo@gmail.com')
                ->to($email)
                ->subject('Confirmation de commande')
                ->html($emailBody);

            $mailer->send($email);

            $adminUsers = $userRepository->findByRole('ROLE_ADMIN');

            foreach ($adminUsers as $admin) {
                $adminEmailBody = '
    <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
        <table style="width: 100%; max-width: 600px; margin: auto; background: white; border-radius: 8px; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);">
            <tr>
                <td style="text-align: center;">
                    <h2 style="color: #333;">Nouvelle commande reçue</h2>
                    <p style="color: #666; font-size: 14px;">Vous avez reçu une nouvelle commande. Veuillez vous connecter à votre espace administrateur pour traiter cette commande.</p>
                </td>
            </tr>
            <tr>
                <td style="text-align: center; padding: 20px; color: #888; font-size: 12px;">
                    © ' . date('Y') . ' Burgererie - Tous droits réservés.
                </td>
            </tr>
        </table>
    </div>';

                $adminNotification = (new Email())
                    ->from('serviceclientdealo@gmail.com')
                    ->to($admin->getEmail())
                    ->subject('Nouvelle commande reçue')
                    ->html($adminEmailBody);

                $mailer->send($adminNotification);
            }

            $this->addFlash('success', 'Commande confirmée !');

            return $this->redirectToRoute('home', ['clearCart' => 1]);
        }

        return $this->render('pages/order.html.twig', [
            'address' => $addressData
        ]);
    }
}

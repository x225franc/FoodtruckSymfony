<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_index')]
    public function index(): Response
    {
        return $this->render('admin/admin.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/user', name: 'admin_user')]
    public function user(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        return $this->render('admin/adminUser.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/ban/{id}', name: 'admin_ban_user')]
    public function banUser(User $user, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user->setRoles(['ROLE_BANNED']);
        $entityManager->flush();

        $email = (new Email())
            ->from($_ENV['MAIL_USER'])
            ->to($user->getEmail())
            ->subject('Votre compte a été banni')
            ->html('<p>Votre compte a été banni.</p>');

        $mailer->send($email);

        $this->addFlash('success', 'Utilisateur banni et notifié.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/unban/{id}', name: 'admin_unban_user')]
    public function unbanUser(User $user, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();

        // Send email notification
        $email = (new Email())
            ->from($_ENV['MAIL_USER'])
            ->to($user->getEmail())
            ->subject('Votre compte a été rétabli')
            ->html('<p>Votre compte a été rétabli.</p>');

        $mailer->send($email);

        $this->addFlash('success', 'Utilisateur rétabli et notifié.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/category', name: 'admin_category')]
    public function category(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            if ($request->request->has('add_category')) {
                $category = new Category();
                $category->setTitle($request->request->get('title'));
                $category->setDescription($request->request->get('description'));
                $entityManager->persist($category);
                $entityManager->flush();
                $this->addFlash('success', 'Catégorie ajoutée avec succès');
            } elseif ($request->request->has('edit_category')) {
                $category = $entityManager->getRepository(Category::class)->find($request->request->get('id'));
                if ($category) {
                    $category->setTitle($request->request->get('title'));
                    $category->setDescription($request->request->get('description'));
                    $entityManager->flush();
                    $this->addFlash('success', 'Catégorie modifiée avec succès');
                } else {
                    $this->addFlash('error', 'Catégorie non trouvée');
                }
            } elseif ($request->request->has('delete_category')) {
                $category = $entityManager->getRepository(Category::class)->find($request->request->get('id'));
                if ($category) {
                    $products = $entityManager->getRepository(Product::class)->findBy(['category' => $category]);

                    foreach ($products as $product) {
                        if ($product->getImage()) {
                            $imagePath = $this->getParameter('images_directory') . '/' . $product->getImage();
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                        $entityManager->remove($product);
                    }

                    $entityManager->remove($category);
                    $entityManager->flush();
                    $this->addFlash('success', 'Catégorie et ses produits associés supprimés avec succès');
                } else {
                    $this->addFlash('error', 'Catégorie non trouvée');
                }
            }
        }

        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('admin/adminCategory.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/product', name: 'admin_product')]
    public function product(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            if ($request->request->has('add_product')) {
                $product = new Product();
                $product->setTitle($request->request->get('title'));
                $product->setDescription($request->request->get('description'));
                $product->setPrice($request->request->get('price'));
                $product->setCategory($entityManager->getRepository(Category::class)->find($request->request->get('category_id')));

                /** @var UploadedFile $imageFile */
                $imageFile = $request->files->get('image');
                if ($imageFile && in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
                    $newFilename = bin2hex(random_bytes(10)) . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $product->setImage($newFilename);
                }

                $entityManager->persist($product);
                $entityManager->flush();
                $this->addFlash('success', 'Produit ajouté avec succès');
            } elseif ($request->request->has('edit_product')) {
                $product = $entityManager->getRepository(Product::class)->find($request->request->get('id'));
                if ($product) {
                    $product->setTitle($request->request->get('title'));
                    $product->setDescription($request->request->get('description'));
                    $product->setPrice($request->request->get('price'));
                    $categoryId = $request->request->get('category_id');
                    if ($categoryId) {
                        $category = $entityManager->getRepository(Category::class)->find($categoryId);
                        if ($category) {
                            $product->setCategory($category);
                        } else {
                            $this->addFlash('error', 'Catégorie non trouvée');
                        }
                    }

                    $entityManager->flush();
                    $this->addFlash('success', 'Produit modifié avec succès');
                } else {
                    $this->addFlash('error', 'Produit non trouvé');
                }
            } elseif ($request->request->has('delete_product')) {
                $product = $entityManager->getRepository(Product::class)->find($request->request->get('id'));
                if ($product) {
                    if ($product->getImage()) {
                        $imagePath = $this->getParameter('images_directory') . '/' . $product->getImage();
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    $entityManager->remove($product);
                    $entityManager->flush();
                    $this->addFlash('success', 'Produit supprimé avec succès');
                } else {
                    $this->addFlash('error', 'Produit non trouvé');
                }
            }
        }

        $products = $entityManager->getRepository(Product::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('admin/adminProduct.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}

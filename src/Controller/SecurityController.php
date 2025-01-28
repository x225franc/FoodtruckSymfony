<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class SecurityController extends AbstractController
{

    protected function validateRegistration(User $user, EntityManagerInterface $entityManager, ValidatorInterface $validator): array
    {
        $errors = [];

        $validationErrors = $validator->validate($user);

        foreach ($validationErrors as $violation) {
            $errors[] = $violation->getMessage();
        }

        if (strlen($user->getPassword()) < 8) {
            $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
        }

        if (!preg_match('/[A-Z]/', $user->getPassword())) {
            $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }

        if (!preg_match('/\d/', $user->getPassword())) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }

        if (!preg_match('/[\W_]/', $user->getPassword())) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        if (strlen($user->getUsername()) < 3) {
            $errors[] = 'Le nom d\'utilisateur doit comporter au moins 3 caractères.';
        }

        if (empty($user->getEmail())) {
            $errors[] = 'L\'email ne peut pas être vide.';
        } elseif (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            $errors[] = 'Cet email est déjà utilisé.';
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()]);
        if ($existingUser) {
            $errors[] = 'Ce nom d\'utilisateur est déjà pris.';
        }

        return $errors;
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Redirige l'utilisateur connecté vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validateRegistration($user, $entityManager, $validator);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                // Hash du mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashedPassword);

                $user->setRoles(['ROLE_USER']);

                $entityManager->persist($user);
                $entityManager->flush();

                // Message de succès
                $this->addFlash('success', 'Votre compte a été créé avec succès.');

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // Récupère les erreurs de la dernière tentative de connexion
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier email utilisé pour la connexion
        $lastEmail = $authenticationUtils->getLastUsername();

        // Ajoute le flash uniquement en cas d'erreur
        if ($error) {
            if ($error instanceof CustomUserMessageAuthenticationException && $error->getMessageKey() === 'Votre compte a été banni. Vous ne pouvez pas vous connecter.') {
                return $this->redirectToRoute('banned');
            } else {
                $this->addFlash('error', 'Connexion échouée, veuillez vérifier vos identifiants.');
            }
        }

        return $this->render('security/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/banned', name: 'banned')]
    public function banned(): Response
    {
        return $this->render('security/banned.html.twig', [], new Response('', 401));
    }

    #[Route("/logout", name: "logout")]
    public function logout(Security $security): Response
    {
        // Le processus de déconnexion est géré par Symfony automatiquement
        $security->logout();

        return $this->redirectToRoute('home');
    }

    #[Route('/forgotpassword', name: 'forgotpassword')]
    public function request(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            // Recherche de l'utilisateur
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Génération d'un jeton unique
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour')); // Expire dans 1 heure

                $entityManager->flush();

                // Envoi de l'email
                $resetPasswordUrl = $this->generateUrl('resetpassword', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new Email())
                    ->from($_ENV['MAIL_USER'])
                    ->to($email)
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html('
    <div class="row">
        <div class="col-12">
            <table class="body-wrap" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; background-color: transparent; margin: 0;">
                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                    <td style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;" valign="top"></td>
                    <td class="container" width="600" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; display: block !important; max-width: 600px !important; clear: both !important; margin: 0 auto;" valign="top">
                        <div class="content" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; max-width: 600px; display: block; margin: 0 auto; padding: 20px;">
                            <table class="main" width="100%" cellpadding="0" cellspacing="0" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; border-radius: 3px; margin: 0; border: none;">
                                <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-wrap aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;padding: 20px; color: #495057; border: 2px solid #1d1e3a;border-radius: 7px; background-color: #fff;" align="center" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                            <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                <td class="content-block" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                                    <h2 class="aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,\'Lucida Grande\',sans-serif; box-sizing: border-box; font-size: 24px; color: #000; line-height: 1.2em; font-weight: 400; text-align: center; margin: 40px 0 0;" align="center">Réinitialisation de votre mot de passe</h2>
                                                </td>
                                            </tr>
                                            <tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                <td class="content-block aligncenter" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top">
                                                    <p style="font-size: 16px; color: #000;">Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :</p>
                                                    <a href="' . $resetPasswordUrl . '" style="background-color: #1d1e3a; color: #ffffff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 16px; display: inline-block; margin-top: 20px;">Réinitialiser mon mot de passe</a>
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
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>');


                $mailer->send($emailMessage);

                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
                // Rediriger vers la page de connexion apres 2 secondes
                sleep(2);
                return $this->redirectToRoute('login');
            } else {
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
            }
        }

        return $this->render('security/forgotpassword.html.twig');
    }

    #[Route('/resetpassword/{token}', name: 'resetpassword')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$token) {
            return $this->redirectToRoute('forgotpassword');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('forgotpassword');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');

            return $this->redirectToRoute('login');
        }

        return $this->render('security/resetpassword.html.twig', [
            'token' => $token,
        ]);
    }

    #[Route('/access-denied', name: 'access_denied')]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig');
    }
}

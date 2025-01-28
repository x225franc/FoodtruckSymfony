<?php

namespace App\EventListener;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (in_array('ROLE_BANNED', $user->getRoles())) {
            throw new CustomUserMessageAuthenticationException('Votre compte a été banni. Vous ne pouvez pas vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}

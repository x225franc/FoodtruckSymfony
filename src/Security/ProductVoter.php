<?php

namespace App\Security;

use App\Entity\Product;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductVoter extends Voter
{
    const EDIT = 'edit';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT]) && $subject instanceof Product;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        // On verifie si l'utilisateur est autorisé à modifier le produit
        switch ($attribute) {
            case self::EDIT:
                return $subject->getOwner() === $user || in_array('ROLE_ADMIN', $user->getRoles());
        }

        return false;
    }
}

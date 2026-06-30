<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RevocationUserChecker.
 *
 * Implémente l'UserCheckerInterface pour gérer les vérifications lors du
 * processus d'authentification pour l'accès à l'admin.
 * Vérifie si l'utilisateur, son accès, a été révoqué.
 */
class RevocationUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        /**
         * @var User $user
         */
        if (true === $user->isRevocated()) {
            throw new CustomUserMessageAccountStatusException(message: 'Accès révoqué');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}

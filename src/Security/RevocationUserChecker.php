<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RevocationUserChecker.
 *
 * Implements the UserCheckerInterface to handle checks during the
 * authentication process for API access.
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

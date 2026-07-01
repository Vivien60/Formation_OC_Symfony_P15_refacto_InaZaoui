<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Récupère les invités visibles publiquement,
 * c'est-à-dire les comptes non administrateurs et non révoqués.
 */
class GetVisibleUsers
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @return array<User>
     */
    public function all(): array
    {
        return $this->userRepository->findBy(['admin' => false, 'revocated' => false]);
    }

    /**
     * @throws \Exception
     */
    public function one(int $id): ?User
    {
        $aUser = $this->userRepository->findOneBy(['id' => $id, 'admin' => false, 'revocated' => false]);

        if (empty($aUser)) {
            return null;
        }

        return $aUser;
    }
}

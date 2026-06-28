<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class GetActiveUsers
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
        $aUser = $this->userRepository->findBy(['id' => $id, 'admin' => false, 'revocated' => false]);

        if (count($aUser) > 1) {
            throw new \Exception('More than one user with the same id');
        }

        if (count($aUser) < 1) {
            return null;
        }

        return $aUser[0];
    }
}

<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserService
{
    function __construct(
        // private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function getUsers(): array
    {
        $users = $this->userRepository->findAll();
        $result = [];
        foreach ($users as $user) {
            $result[] = $user->getName();
        }

        return $result;
    }

    public function register(): void
    {

    }
}
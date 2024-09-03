<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    function __construct(
        private readonly UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function getUsers(): array
    {

    }
}
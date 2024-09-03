<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('users')]
class UserController extends AbstractController
{
    function __construct()
    {

    }

    #[Route(path: '/users', name: 'apiGetUsers', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {

    }
}
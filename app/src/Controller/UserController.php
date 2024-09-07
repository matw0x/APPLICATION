<?php

namespace App\Controller;

use App\Helper\DTO\LoginDTO;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\UserService;
use App\Service\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    function __construct(
        private readonly UserService         $userService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorService    $validatorService,
        private readonly YandexMailerService $yandexMailerService,
        private readonly MagicLinkService    $magicLinkService,
    )
    {
    }

    #[Route(path: '/all', name: 'apiGetUsers', methods: Request::METHOD_GET)]
    public function getUsers(): JsonResponse
    {
        return $this->json(
            data: $this->userService->getUsers(),
            status: Response::HTTP_OK
        );
    }

    #[Route(path: '/register', name: 'apiRegUser', methods: Request::METHOD_POST)]
    public function register(Request $request): JsonResponse
    {
        $data = $this->serializer->deserialize($request->getContent(), LoginDTO::class, 'json');
        $this->validatorService->validate($data, ['register']);

        $this->userService->register($data->email, $this->magicLinkService, $this->yandexMailerService);

        return $this->json(
            data: 'Link was sent! Check your email',
            status: Response::HTTP_CREATED
        );
    }

    #[Route(path: '/verify', name: 'apiVerifyReg', methods: Request::METHOD_POST)]
    public function verify(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        return $this->json(
            $token
        );
    }
}
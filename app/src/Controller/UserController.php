<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    function __construct(
        private readonly UserService         $userService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface  $validator,
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
//        $data = $this->serializer->deserialize($request->getContent(), User::class, 'json');
//        $this->validator->validate($data, ['register']);

        $data = json_decode($request->getContent(), true);

        $user = (new User())
            ->setName($data['name'])
            ->setSurname($data['surname'])
            ->setEmail($data['email'])
            ->setRole($data['role']);

        $this->userService->register($user->getEmail(), $this->magicLinkService, $this->yandexMailerService);

        return $this->json(
            data: 'Link was sent! Check your email',
            status: Response::HTTP_CREATED
        );
    }

    #[Route(path: '/verify', name: 'apiVerifyAccount', methods: Request::METHOD_POST)]
    public function verify(Request $request): JsonResponse
    {

        return $this->json(
            'x'
        );
    }
}
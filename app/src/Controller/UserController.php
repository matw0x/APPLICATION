<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\DTO\RegisterDTO;
use App\Helper\Exception\ApiException;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\UserService;
use App\Service\ValidatorService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly UserService            $userService,
        private readonly SerializerInterface    $serializer,
        private readonly ValidatorService       $validatorService,
        private readonly YandexMailerService    $yandexMailerService,
        private readonly MagicLinkService       $magicLinkService,
        private readonly EntityManagerInterface $entityManager
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
        $data = $this->serializer->deserialize($request->getContent(), RegisterDTO::class, 'json');
        $this->validatorService->validate($data, ['register']);

        $this->userService->register($data, $this->magicLinkService, $this->yandexMailerService);

        return $this->json(
            data: 'Link was sent! Check your email',
            status: Response::HTTP_OK
        );
    }

    #[Route(path: '/verify', name: 'apiVerifyEmail', methods: Request::METHOD_POST)]
    public function verify(Request $request): JsonResponse
    {
        $registerDTO = $this->serializer->deserialize($request->getContent(), RegisterDTO::class, 'json');
        $token = $request->query->get(MagicLinkService::TOKEN);

        return $this->json(
            data: $this->userService->verify($token, $registerDTO),
            status: Response::HTTP_CREATED
        );
    }

    #[Route(path: '/look/{id<\d+>}', name: 'apiLook', methods: Request::METHOD_GET)]
    public function look(int $id, Request $request): JsonResponse
    {
        $userToView = $this->entityManager->getRepository(User::class)->find($id);
        if (!$userToView)
        {
            throw new ApiException(
                message: 'Пользователь не найден',
                status: Response::HTTP_NOT_FOUND
            );
        }

        $accessToken = $request->headers->get(MagicLinkService::TOKEN);

        return $this->json(
            data: $this->userService->look($userToView, $accessToken),
            status: Response::HTTP_OK
        );
    }

//    #[Route(path: '/edit', name: 'apiEditUser', methods: Request::METHOD_PUT)]
//    public function editUser(Request $request): JsonResponse
//    {
//        $data = $this->serializer->deserialize($request->getContent(), User::class, 'json');
//
//    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\Const\Keywords;
use App\Helper\DTO\User\EditDTO;
use App\Helper\DTO\User\RegisterDTO;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\UserService;
use App\Service\Validator\ValidatorService;
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
        $token = $request->query->get(Keywords::TOKEN);

        return $this->json(
            data: $this->userService->verify($token, $registerDTO),
            status: Response::HTTP_CREATED
        );
    }

    #[Route(path: '/look/{id<\d+>}', name: 'apiLookUser', methods: Request::METHOD_GET)]
    public function lookUser(int $id, Request $request): JsonResponse
    {
        $userToView = $this->entityManager->getRepository(User::class)->find($id);
        $userToView->checkUserNullable($userToView);

        $accessToken = $request->headers->get(Keywords::TOKEN);

        return $this->json(
            data: $this->userService->look($userToView, $accessToken),
            status: Response::HTTP_OK
        );
    }

    #[Route(path: '/edit/{id<\d+>}', name: 'apiEditUser', methods: Request::METHOD_PUT)]
    public function editUser(int $id, Request $request): JsonResponse
    {
        $userToEdit = $this->entityManager->getRepository(User::class)->find($id);
        $userToEdit->checkUserNullable($userToEdit);

        $userData = $this->serializer->deserialize($request->getContent(), EditDTO::class, 'json');
        $this->validatorService->validate(body: $userData, groupsBody: ['edit']);

        $accessToken = $request->headers->get(Keywords::TOKEN);

        return $this->json(
            data: $this->userService->edit($userToEdit, $userData, $accessToken),
            status: Response::HTTP_OK
        );
    }
}
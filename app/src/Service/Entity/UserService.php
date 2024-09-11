<?php

namespace App\Service\Entity;

use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Helper\Const\Keywords;
use App\Helper\DTO\User\EditDTO;
use App\Helper\DTO\User\RegisterDTO;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\Token\TokenService;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserService
{
    function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository         $userRepository,
        private TokenService           $tokenService,
        private MagicLinkService       $magicLinkService,
        private DeviceService          $deviceService,
    )
    {
    }

    public function getUsers(): array // FUNC FOR TESTING!
    {
        $users = $this->userRepository->findAll();
        $result = [];
        foreach ($users as $user) {
            $result[] = $user->getName();
        }

        return $result;
    }

    public function register(RegisterDTO $registerDTO, MagicLinkService $magicLinkService, YandexMailerService $mailerService): void
    {
        $token = $magicLinkService->generateToken();
        $magicLink = $magicLinkService->createMagicLink($token);

        $magicLinkToken = (new MagicLinkToken())
            ->setToken($token)
            ->setStatus(MagicLinkTokenStatus::IS_ACTIVE->value)
            ->setExpiresAt(new \DateTimeImmutable(MagicLinkService::TOKEN_LIFETIME))
            ->setEmail($registerDTO->email);

        $this->entityManager->persist($magicLinkToken);
        $this->entityManager->flush();

        $mailerService->sendMagicLink($registerDTO->email, $magicLink);
    }

    public function verify(string $token, RegisterDTO $registerDTO): array
    {
        $magicLinkToken = $this->entityManager->getRepository(MagicLinkToken::class)->findOneBy([
            Keywords::TOKEN => $token,
            Keywords::STATUS => MagicLinkTokenStatus::IS_ACTIVE->value
        ]);

        $this->tokenService->checkTokenNullable($magicLinkToken);
        $this->magicLinkService->validateMagicLinkToken($magicLinkToken);

        $user = (new User())
            ->setEmail($magicLinkToken->getEmail())
            ->setName($registerDTO->name)
            ->setSurname($registerDTO->surname)
            ->setRole(UserRole::USER->value);

        $device = $this->tokenService->createTokens($user);

        $magicLinkToken
            ->setOwner($user)
            ->setStatus(MagicLinkTokenStatus::IS_USED->value);

        $this->entityManager->persist($user);
        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return [
            Keywords::ACCESS_TOKEN => $device->getAccessToken(),
            Keywords::REFRESH_TOKEN => $device->getRefreshToken()
        ];
    }

    public function look(User $userToView, ?string $accessToken): array
    {
        $this->tokenService->checkTokenNullable($accessToken);
        $watcherDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($watcherDevice);

        $watcherUser = $watcherDevice->getOwner();
        $watcherUser->checkPermission($userToView);

        return [
            'name' => $userToView->getName(),
            'surname' => $userToView->getSurname(),
            'email' => $userToView->getEmail(),
            'role' => $userToView->getRole(),
            'yourAccessToken' => $watcherDevice->getAccessToken()
        ];
    }

    public function edit(User $userToEdit, EditDTO $userData, ?string $accessToken): array
    {
        $this->tokenService->checkTokenNullable($accessToken);
        $editorDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($editorDevice);

        $editorUser = $editorDevice->getOwner();
        $editorUser->checkPermission($userToEdit);

        if ($newName = $userData->name) {
            $userToEdit->setName($newName);
        }

        if ($newSurname = $userData->surname) {
            $userToEdit->setSurname($newSurname);
        }

        $this->entityManager->flush();

        return [
            'name' => $userToEdit->getName(),
            'surname' => $userToEdit->getSurname(),
            'accessToken' => $editorDevice->getAccessToken()
        ];
    }
}
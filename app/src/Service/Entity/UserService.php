<?php

namespace App\Service\Entity;

use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Helper\Const\Keywords;
use App\Helper\DTO\User\Edit\EditRequestDTO;
use App\Helper\DTO\User\Edit\EditResponseDTO;
use App\Helper\DTO\User\Register\RegisterDTO;
use App\Helper\DTO\User\Stats\StatsResponseDTO;
use App\Helper\DTO\User\Verify\VerifyRequestDTO;
use App\Helper\DTO\User\Verify\VerifyResponseDTO;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Trait\UserValidationTrait;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\RedisService;
use App\Service\Token\TokenService;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserService
{
    use UserValidationTrait;

    function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository         $userRepository,
        private TokenService           $tokenService,
        private MagicLinkService       $magicLinkService,
        private DeviceService          $deviceService,
        private RedisService           $redisService
    )
    {
    }

    public function getUsers(): array // FUNC FOR TESTING!
    {
        $cacheKey = 'users_list';
        $cachedUsers = $this->redisService->get($cacheKey);

        if ($cachedUsers !== null) {
            return $cachedUsers;
        }

        $users = $this->userRepository->findAll();
        $result = [];
        foreach ($users as $user) {
            $result[] = $user->getEmail();
        }

        $this->redisService->set($cacheKey, $result);

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

    public function verify(string $token, VerifyRequestDTO $verifyDTO): VerifyResponseDTO
    {
        $magicLinkToken = $this->entityManager->getRepository(MagicLinkToken::class)->findOneBy([
            Keywords::TOKEN => $token,
            Keywords::STATUS => MagicLinkTokenStatus::IS_ACTIVE->value
        ]);

        $this->tokenService->validateTokenExistence($magicLinkToken->getToken());
        $this->magicLinkService->validateMagicLinkToken($magicLinkToken);

        $user = (new User())
            ->setEmail($magicLinkToken->getEmail())
            ->setName($verifyDTO->name)
            ->setSurname($verifyDTO->surname);

        $device = $this->tokenService->createTokens($user);

        $magicLinkToken
            ->setOwner($user)
            ->setStatus(MagicLinkTokenStatus::IS_USED->value);

        $this->entityManager->persist($user);
        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return new VerifyResponseDTO($device);
    }

    public function stats(User $userToView, ?string $accessToken): StatsResponseDTO
    {
        $watcherDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($watcherDevice);

        $watcherUser = $watcherDevice->getOwner();
        $this->validateUserPermission($watcherUser, $userToView);
        $this->validateUserStatus($watcherUser);

        return new StatsResponseDTO($userToView, $watcherDevice);
    }

    public function edit(User $userToEdit, EditRequestDTO $userData, ?string $accessToken): EditResponseDTO
    {
        $editorDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($editorDevice);

        $editorUser = $editorDevice->getOwner();
        $this->validateUserPermission($editorUser, $userToEdit);
        $this->validateUserStatus($editorUser);

        if ($newName = $userData->name) {
            $userToEdit->setName($newName);
        }

        if ($newSurname = $userData->surname) {
            $userToEdit->setSurname($newSurname);
        }

        $this->entityManager->flush();

        return new EditResponseDTO($userToEdit, $editorDevice);
    }

    public function delete(User $userToDelete, ?string $accessToken): string
    {
        $deleterDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($deleterDevice);

        $deleterUser = $deleterDevice->getOwner();
        $this->validateUserPermission($deleterUser, $userToDelete);

        foreach ($userToDelete->getMagicLinkTokens() as $magicLinkToken) {
            $this->entityManager->remove($magicLinkToken);
        }

        foreach ($userToDelete->getDevices() as $device) {
            $this->entityManager->remove($device);
        }

        $this->entityManager->remove($userToDelete);
        $this->entityManager->flush();

        return $deleterDevice->getAccessToken();
    }
}
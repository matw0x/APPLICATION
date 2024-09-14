<?php

namespace App\Service\Entity;

use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Helper\Const\Keywords;
use App\Helper\DTO\User\All;
use App\Helper\DTO\User\Edit\EditRequestDTO;
use App\Helper\DTO\User\Edit\EditResponseDTO;
use App\Helper\DTO\User\Register\RegisterDTO;
use App\Helper\DTO\User\Stats\StatsResponseDTO;
use App\Helper\DTO\User\Verify\VerifyRequestDTO;
use App\Helper\DTO\User\Verify\VerifyResponseDTO;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Enum\UserStatus;
use App\Helper\Exception\ApiException;
use App\Helper\Trait\UserValidationTrait;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\RedisService;
use App\Service\Token\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

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

    public function getUsers(?string $accessToken): array
    {
        $adminDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($adminDevice);

        $adminUser = $adminDevice->getOwner();
        if (!$this->isAdmin($adminUser)) {
            throw new ApiException(message: 'Недостаточно прав для выполнения операции', status: Response::HTTP_FORBIDDEN);
        }

        $cacheKey = 'user_list';
        $cachedData = $this->redisService->get($cacheKey);

        if ($cachedData) {
            return array_map('json_decode', array_values($cachedData));
        }

        $users = $this->userRepository->findAll();
        $result = [];

        foreach ($users as $user) {
            $result[] = new All($user);
        }

        $redisData = [];
        foreach ($result as $index => $userData) {
            $redisData[$index] = json_encode($userData);
        }

        $this->redisService->set($cacheKey, $redisData);
        $this->redisService->expire($cacheKey, 3600);

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

        $cacheKey = 'user_list';
        $this->redisService->delete($cacheKey);

        return new VerifyResponseDTO($device);
    }

    public function stats(User $userToView, ?string $accessToken): StatsResponseDTO
    {
        $watcherDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($watcherDevice);

        $watcherUser = $watcherDevice->getOwner();
        $this->validateUserPermission($watcherUser, $userToView);
        $this->validateUserStatus($watcherUser);

        $cacheKey = 'user_stats_' . $userToView->getId();
        $cachedData = $this->redisService->get($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        $statsResponse = new StatsResponseDTO($userToView, $watcherDevice);
        $this->redisService->set($cacheKey, (array)$statsResponse);
        $this->redisService->expire($cacheKey, 86400);

        return $statsResponse;
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

        $editResponse = new EditResponseDTO($userToEdit, $editorDevice);

        $cacheKeyList = 'user_list';
        $cacheKeyStats = 'user_stats_' . $userToEdit->getId();

        $this->redisService->delete($cacheKeyList);
        $this->redisService->set($cacheKeyStats, (array)$editResponse);
        $this->redisService->expire($cacheKeyStats, 86400);

        return $editResponse;
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

        $cacheKeyList = 'user_list';
        $cacheKeyStats = 'user_stats_' . $userToDelete->getId();

        $this->redisService->delete($cacheKeyList);
        $this->redisService->delete($cacheKeyStats);

        return $deleterDevice->getAccessToken();
    }

    public function block(User $userToBlock, ?string $accessToken): string
    {
        $blockerDevice = $this->deviceService->getDeviceByAccessToken($accessToken);
        $this->tokenService->refreshTokens($blockerDevice);

        $blockerUser = $blockerDevice->getOwner();
        if ($this->isAdmin($blockerUser)) {
            throw new ApiException(
                message: 'Ваша роль не администратор!',
                status: Response::HTTP_FORBIDDEN
            );
        }

        $userToBlock->setStatus(UserStatus::BLOCKED->value);
        $this->entityManager->flush();

        $cacheKeyStats = 'user_stats_' . $userToBlock->getId();
        $this->redisService->delete($cacheKeyStats);

        $cacheKeyList = 'user_list';
        $this->redisService->delete($cacheKeyList);

        return $blockerDevice->getAccessToken();
    }
}
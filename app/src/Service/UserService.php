<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Helper\Const\Keywords;
use App\Helper\DTO\User\EditDTO;
use App\Helper\DTO\User\RegisterDTO;
use App\Helper\Enum\DeviceStatus;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Enum\UserRole;
use App\Helper\Exception\ApiException;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use App\Service\Token\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class UserService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository         $userRepository,
        private readonly TokenService           $tokenService,
    )
    {
    }

    private function checkDeviceNullable(?Device $device): void
    {
        if (!$device)
        {
            throw new ApiException(
                message: 'Устройство не найдено или не активно',
                status: Response::HTTP_NOT_FOUND
            );
        }
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

        if ($magicLinkToken->getExpiresAt() < new \DateTimeImmutable())
        {
            $magicLinkToken->setStatus(MagicLinkTokenStatus::IS_NOT_ALIVE->value);
            $this->entityManager->flush();

            throw new ApiException(
                message: 'Время действия ссылки истекло',
                status: Response::HTTP_FORBIDDEN
            );
        }

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

    private function checkTokensLifetime(Device $device, \DateTimeImmutable $currentDateTime): void
    {
        if ($device->getAccessTokenExpiresAt() < $currentDateTime)
        {
            if ($device->getRefreshTokenExpiresAt() < $currentDateTime)
            {
                $device->setStatus(DeviceStatus::INACTIVE->value);
                $this->entityManager->flush();

                throw new ApiException(
                    message: 'Время жизни токенов истекло. Требуется повторная аутентификация',
                    status: Response::HTTP_UNAUTHORIZED
                );
            }

            $device->refreshTokens($currentDateTime);
            $this->entityManager->flush();
        }
    }

    public function look(User $userToView, ?string $accessToken): array
    {
        $this->checkTokenNullable($accessToken);

        $watcherDevice = $this->entityManager->getRepository(Device::class)->findOneBy([
            Keywords::ACCESS_TOKEN => $accessToken,
            Keywords::STATUS => DeviceStatus::ACTIVE->value
        ]);

        $this->checkDeviceNullable($watcherDevice);

        $currentDateTime = new \DateTimeImmutable();
        $this->checkTokensLifetime($watcherDevice, $currentDateTime);

        $watcherUser = $watcherDevice->getOwner();
        $watcherUser->checkCanCrud($userToView);

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
        $this->checkTokenNullable($accessToken);

        $editorDevice = $this->entityManager->getRepository(Device::class)->findOneBy([
            Keywords::ACCESS_TOKEN => $accessToken,
            Keywords::STATUS => DeviceStatus::ACTIVE->value
        ]);

        $this->checkDeviceNullable($editorDevice);

        $currentDateTime = new \DateTimeImmutable();
        $this->checkTokensLifetime($editorDevice, $currentDateTime);

        $editorUser = $editorDevice->getOwner();
        $editorUser->checkCanCrud($userToEdit);

        if ($userData->name)
        {
            $userToEdit->setName($userData->name);
        }

        if ($userData->surname)
        {
            $userToEdit->setSurname($userData->surname);
        }

        $this->entityManager->flush();

        return [
            'name' => $userToEdit->getName(),
            'surname' => $userToEdit->getSurname(),
            'accessToken' => $accessToken
        ];
    }
}
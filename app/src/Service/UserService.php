<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Helper\DTO\RegisterDTO;
use App\Helper\Enum\DeviceStatus;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Enum\UserRole;
use App\Helper\Exception\ApiException;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class UserService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository         $userRepository,
    )
    {
    }

    public function getUsers(): array
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
            MagicLinkService::TOKEN => $token,
            MagicLinkService::STATUS => MagicLinkTokenStatus::IS_ACTIVE->value
        ]);

        if (!$magicLinkToken)
        {
            throw new ApiException(
                message: 'Токен не найден',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        if ($magicLinkToken->getExpiresAt() < new \DateTimeImmutable())
        {
            $magicLinkToken->setStatus(MagicLinkTokenStatus::IS_NOT_ALIVE->value);
            $this->entityManager->flush();

            throw new ApiException(
                message: 'Время жизни токена вышло',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $user = (new User())
            ->setEmail($magicLinkToken->getEmail())
            ->setName($registerDTO->name)
            ->setSurname($registerDTO->surname)
            ->setRole(UserRole::USER->value);

        $device = (new Device())
            ->setOwner($user);

        $magicLinkToken
            ->setOwner($user)
            ->setStatus(MagicLinkTokenStatus::IS_USED->value);

        $this->entityManager->persist($user);
        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return [
            Device::ACCESS_TOKEN => $device->getAccessToken(),
            Device::REFRESH_TOKEN => $device->getRefreshToken()
        ];
    }

    public function look(User $userToView, ?string $accessToken): array
    {
        if (!$accessToken)
        {
            throw new ApiException(
                message: 'Пропущен токен в заголовке',
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        $watcherDevice = $this->entityManager->getRepository(Device::class)->findOneBy([
            Device::ACCESS_TOKEN => $accessToken,
            Device::STATUS => DeviceStatus::ACTIVE->value
        ]);

        if (!$watcherDevice)
        {
            throw new ApiException(
                message: 'Просматривающее устройство не найдено или не активно',
                status: Response::HTTP_NOT_FOUND
            );
        }

        $currentDateTime = new \DateTimeImmutable();

        if ($watcherDevice->getAccessTokenExpiresAt() < $currentDateTime)
        {
            if ($watcherDevice->getRefreshTokenExpiresAt() < $currentDateTime)
            {
                throw new ApiException(
                    message: 'Время жизни токенов истекло. Требуется повторная аутентификация',
                    status: Response::HTTP_UNAUTHORIZED
                );
            }

            $watcherDevice->refreshTokens($currentDateTime);
            $this->entityManager->flush();
        }

        $watcherUser = $watcherDevice->getOwner();
        if (!$watcherUser->canViewProfile($userToView))
        {
            throw new ApiException(
                message: 'Недостаточно прав для просмотра профиля',
                status: Response::HTTP_FORBIDDEN
            );
        }

        return [
            'name' => $userToView->getName(),
            'surname' => $userToView->getSurname(),
            'email' => $userToView->getEmail(),
            'role' => $userToView->getRole(),
            'newAccessToken' => $watcherDevice->getAccessToken()
        ];
    }
}
<?php

namespace App\Service\Token;

use App\Entity\Device;
use App\Entity\User;
use App\Helper\Enum\DeviceStatus;
use App\Helper\Exception\ApiException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class TokenService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    const ACCESS_TOKEN_LIFETIME = '30 minutes';
    const REFRESH_TOKEN_LIFETIME = '30 days';
    const MINIMAL_DAYS_TO_UPDATE = 7;

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function createTokens(User $user): Device
    {
        return (new Device())
            ->setOwner($user)
            ->setAccessToken($this->generateToken())
            ->setRefreshToken($this->generateToken())
            ->setAccessTokenExpiresAt((new DateTimeImmutable())->modify(self::ACCESS_TOKEN_LIFETIME))
            ->setRefreshTokenExpiresAt((new DateTimeImmutable())->modify(self::REFRESH_TOKEN_LIFETIME));
    }

    public function validateTokenExistence(?string $token): void
    {
        if (!$token)
        {
            throw new ApiException(
                message: 'Пропущен токен в заголовке',
                status: Response::HTTP_UNAUTHORIZED
            );
        }
    }

    public function isExpiredAccessToken(Device $device): bool
    {
        return (new DateTimeImmutable()) > $device->getAccessTokenExpiresAt();
    }

    public function isExpiredRefreshToken(Device $device): bool
    {
        return (new DateTimeImmutable()) > $device->getRefreshTokenExpiresAt();
    }

    public function refreshTokens(Device $device): void
    {
        if ($this->isExpiredAccessToken($device)) {

            if ($this->isExpiredRefreshToken($device)) {
                $device->setStatus(DeviceStatus::INACTIVE->value);
                $this->entityManager->flush();

                throw new ApiException(
                    message: 'Рефреш токен истёк. Требуется аутентификация',
                    status: Response::HTTP_UNAUTHORIZED
                );
            }

            // если до конца жизни refresh токена осталось меньше N дней, обновляем его
            $daysUntilExpiration = $device->getRefreshTokenExpiresAt()->diff(new DateTimeImmutable())->days;
            if ($daysUntilExpiration <= self::MINIMAL_DAYS_TO_UPDATE) {
                $device->setRefreshToken($this->generateToken());
                $device->setRefreshTokenExpiresAt((new DateTimeImmutable())->modify(self::REFRESH_TOKEN_LIFETIME));
            }

            $device->setAccessToken($this->generateToken());
            $device->setAccessTokenExpiresAt((new DateTimeImmutable)->modify(self::ACCESS_TOKEN_LIFETIME));

            $this->entityManager->flush();
        }
    }
}
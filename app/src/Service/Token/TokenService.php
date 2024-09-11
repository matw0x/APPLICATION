<?php

namespace App\Service\Token;

use App\Entity\Device;
use App\Entity\User;
use App\Helper\Exception\ApiException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class TokenService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager,

    )
    {
    }

    const ACCESS_TOKEN_LIFETIME = '30 minutes';
    const REFRESH_TOKEN_LIFETIME = '30 days';

    public static function generateToken(): string
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

    public function checkTokenNullable(?string $token): void
    {
        if (!$token)
        {
            throw new ApiException(
                message: 'Пропущен токен в заголовке',
                status: Response::HTTP_UNAUTHORIZED
            );
        }
    }

    public function validateAccessToken(Device $device): bool
    {
        return (new DateTimeImmutable()) <= $device->getAccessTokenExpiresAt();
    }

    public function validateRefreshToken(Device $device): bool
    {
        return (new DateTimeImmutable()) <= $device->getRefreshTokenExpiresAt();
    }

    public function refreshTokens(Device $device): void
    {
        $currentDateTime = new \DateTimeImmutable();


    }
}
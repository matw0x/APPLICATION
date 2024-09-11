<?php

namespace App\Service\MagicLink;

use App\Entity\MagicLinkToken;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Helper\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class MagicLinkService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    const BASE_URL = 'localhost:8080';
    const TOKEN_LIFETIME = '30 minutes';

    public function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function createMagicLink(string $token): string
    {
        return sprintf('%s/api/users/verify?token=%s', self::BASE_URL, $token);
    }

    public function validateMagicLinkToken(MagicLinkToken $token): void
    {
        if ($token->getExpiresAt() < new \DateTimeImmutable()) {
            $token->setStatus(MagicLinkTokenStatus::IS_NOT_ALIVE->value);
            $this->entityManager->flush();

            throw new ApiException(
                message: 'Время действия ссылки истекло',
                status: Response::HTTP_FORBIDDEN
            );
        }
    }
}
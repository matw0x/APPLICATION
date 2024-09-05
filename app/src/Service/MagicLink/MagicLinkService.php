<?php

namespace App\Service\MagicLink;

use Ramsey\Uuid\Uuid;

class MagicLinkService
{
    const BASE_URL = 'localhost:8080';
    public function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function createMagicLink(string $token): string
    {
        return sprintf('%s/verify?token=%s', self::BASE_URL, $token);
    }
}
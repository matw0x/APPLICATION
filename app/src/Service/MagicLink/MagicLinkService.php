<?php

namespace App\Service\MagicLink;

use Ramsey\Uuid\Uuid;

class MagicLinkService
{
    const BASE_URL = 'localhost:8080';
    const TOKEN_LIFETIME = '+30 minutes';
    const TOKEN = 'token';
    const STATUS = 'status';

    public function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function createMagicLink(string $token): string
    {
        return sprintf('%s/api/users/verify?token=%s', self::BASE_URL, $token);
    }
}
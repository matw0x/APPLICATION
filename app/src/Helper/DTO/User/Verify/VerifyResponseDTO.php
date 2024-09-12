<?php

namespace App\Helper\DTO\User\Verify;

use App\Entity\Device;

class VerifyResponseDTO
{
    private string $accessToken;
    private string $refreshToken;

    function __construct(Device $device)
    {
        $this->accessToken = $device->getAccessToken();
        $this->refreshToken = $device->getRefreshToken();
    }
}
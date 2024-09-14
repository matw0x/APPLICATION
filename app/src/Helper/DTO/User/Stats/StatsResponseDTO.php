<?php

namespace App\Helper\DTO\User\Stats;

use App\Entity\Device;
use App\Entity\User;

class StatsResponseDTO
{
    public string $name;
    public string $surname;
    public string $email;
    public string $accessToken;

    function __construct(User $user, Device $device)
    {
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->email = $user->getEmail();
        $this->accessToken = $device->getAccessToken();
    }
}
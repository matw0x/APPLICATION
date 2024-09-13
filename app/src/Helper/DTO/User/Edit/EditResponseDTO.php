<?php

namespace App\Helper\DTO\User\Edit;

use App\Entity\Device;
use App\Entity\User;

class EditResponseDTO
{
    public string $name;
    public string $surname;
    public string $accessToken;

    function __construct(User $user, Device $device)
    {
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->accessToken = $device->getAccessToken();
    }
}
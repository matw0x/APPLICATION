<?php

namespace App\Helper\DTO\User\Edit;

use App\Entity\Device;
use App\Entity\User;

class EditResponseDTO
{
    private string $name;
    private string $surname;
    private string $accessToken;

    function __construct(User $user, Device $device)
    {
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->accessToken = $device->getAccessToken();
    }
}
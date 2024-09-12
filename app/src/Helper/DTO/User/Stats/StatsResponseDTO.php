<?php

namespace App\Helper\DTO\User\Stats;

use App\Entity\Device;
use App\Entity\User;

class StatsResponseDTO
{
    private int $id;
    private string $name;
    private string $surname;
    private string $email;
    private string $role;
    private int $status;
    private string $accessToken;

    function __construct(User $user, Device $device)
    {
        $this->id = $user->getId();
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->email = $user->getEmail();
        $this->role = $user->getRole();
        $this->status = $user->getStatus();
        $this->accessToken = $device->getAccessToken();
    }
}
<?php

namespace App\Helper\DTO\User\Stats;

use App\Entity\Device;
use App\Entity\User;

class StatsResponseDTO
{
    public int $id;
    public string $name;
    public string $surname;
    public string $email;
    public string $role;
    public int $status;
    public string $accessToken;

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
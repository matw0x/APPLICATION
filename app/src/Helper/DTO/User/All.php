<?php

namespace App\Helper\DTO\User;

use App\Entity\User;

class All
{
    public int $id;
    public string $name;
    public string $surname;
    public string $email;
    public string $role;
    public int $status;


    function __construct(User $user)
    {
        $this->id = $user->getId();
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->email = $user->getEmail();
        $this->role = $user->getRole();
        $this->status = $user->getStatus();
    }
}
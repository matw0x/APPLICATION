<?php

namespace App\Helper\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDTO
{
    #[Assert\NotNull(groups: ['register'])]
    #[Assert\Type(type: 'string', groups: ['register'])]
    #[Assert\Length(max: 63, groups: ['register'])]
    public string $email;

    public string $token;
}
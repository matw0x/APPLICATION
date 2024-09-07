<?php

namespace App\Helper\DTO;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterDTO
{
    #[Assert\NotNull(groups: ['register'])]
    #[Assert\Type(type: Types::STRING, groups: ['register'])]
    #[Assert\Length(max: 63, groups: ['register'])]
    public string $email;

    #[Assert\NotNull(groups: ['register'])]
    #[Assert\Type(type: Types::STRING, groups: ['register'])]
    #[Assert\Length(max: 63, groups: ['register'])]
    public string $name;

    #[Assert\NotNull(groups: ['register'])]
    #[Assert\Type(type: Types::STRING, groups: ['register'])]
    #[Assert\Length(max: 63, groups: ['register'])]
    public string $surname;
}
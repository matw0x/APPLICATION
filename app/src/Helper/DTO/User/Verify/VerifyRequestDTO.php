<?php

namespace App\Helper\DTO\User\Verify;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

class VerifyRequestDTO
{
    #[Assert\NotNull(groups: ['verify'])]
    #[Assert\Type(type: Types::STRING, groups: ['verify'])]
    #[Assert\Length(max: 63, groups: ['verify'])]
    public string $name;

    #[Assert\NotNull(groups: ['verify'])]
    #[Assert\Type(type: Types::STRING, groups: ['verify'])]
    #[Assert\Length(max: 63, groups: ['verify'])]
    public string $surname;
}
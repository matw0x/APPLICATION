<?php

namespace App\Helper\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;

class EditDTO
{
    #[Assert\Type(type: Types::STRING, groups: ['edit'])]
    #[Assert\Length(max: 63, groups: ['edit'])]
    public ?string $name = null;

    #[Assert\Type(type: Types::STRING, groups: ['edit'])]
    #[Assert\Length(max: 63, groups: ['edit'])]
    public ?string $surname= null;
}
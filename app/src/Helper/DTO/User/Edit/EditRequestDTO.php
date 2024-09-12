<?php

namespace App\Helper\DTO\User\Edit;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

class EditRequestDTO
{
    #[Assert\Type(type: Types::STRING, groups: ['edit'])]
    #[Assert\Length(max: 63, groups: ['edit'])]
    public ?string $name = null;

    #[Assert\Type(type: Types::STRING, groups: ['edit'])]
    #[Assert\Length(max: 63, groups: ['edit'])]
    public ?string $surname= null;
}
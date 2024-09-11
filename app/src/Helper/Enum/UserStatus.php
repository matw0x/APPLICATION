<?php

namespace App\Helper\Enum;

enum UserStatus: int
{
    case ACTIVE = 1;
    case BLOCKED = 10;
}

<?php

namespace App\Helper\Enum;

enum MagicLinkTokenStatus: int
{
    case IS_ACTIVE = 1;
    case IS_USED = 10;
    case IS_NOT_ALIVE = 20;
}
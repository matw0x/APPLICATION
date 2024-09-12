<?php

namespace App\Helper\Trait;

use App\Entity\User;
use App\Helper\Enum\UserRole;
use App\Helper\Enum\UserStatus;
use App\Helper\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

trait UserValidationTrait
{
    public function validateUserExistence(?User $user): void
    {
        if (!$user)
        {
            throw new ApiException(
                message: 'Пользователь не найден',
                status: Response::HTTP_NOT_FOUND
            );
        }
    }

    public function validateUserPermission(User $checkerUser, User $userToCheck): void
    {
        if (!($checkerUser->getRole() === UserRole::ADMIN->value || $checkerUser->getId() === $userToCheck->getId()))
        {
            throw new ApiException(
                message: 'Недостаточно прав для выполнения данной операции',
                status: Response::HTTP_FORBIDDEN
            );
        }
    }

    public function validateUserStatus(User $user): void
    {
        if ($user->getStatus() === UserStatus::BLOCKED->value)
        {
            throw new ApiException(
                message: 'Пользователь заблокирован',
                status: Response::HTTP_FORBIDDEN
            );
        }
    }
}
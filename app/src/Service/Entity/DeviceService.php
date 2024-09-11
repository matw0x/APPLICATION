<?php

namespace App\Service\Entity;

use App\Entity\Device;
use App\Helper\Const\Keywords;
use App\Helper\Enum\DeviceStatus;
use App\Helper\Exception\ApiException;
use App\Service\Token\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class DeviceService
{
    function __construct(
        private EntityManagerInterface $entityManager,
        private TokenService           $tokenService
    )
    {
    }

    public function validateDeviceExistence(?Device $device): void
    {
        if (!$device) {
            throw new ApiException(
                message: 'Устройство не найдено или не активно',
                status: Response::HTTP_NOT_FOUND
            );
        }
    }

    public function getDeviceByAccessToken(?string $accessToken): Device
    {
        $this->tokenService->checkTokenNullable($accessToken);

        $device = $this->entityManager->getRepository(Device::class)->findOneBy([
            Keywords::ACCESS_TOKEN => $accessToken,
            Keywords::STATUS => DeviceStatus::ACTIVE->value
        ]);

        $this->validateDeviceExistence($device);

        return $device;
    }
}
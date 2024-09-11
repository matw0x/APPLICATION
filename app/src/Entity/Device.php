<?php

namespace App\Entity;

use App\Helper\Enum\DeviceStatus;
use App\Repository\DeviceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    function __construct()
    {
        $this->status = DeviceStatus::ACTIVE->value;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue('SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::INTEGER)]
    private int $status;

    #[ORM\Column(type: Types::STRING, length: 127)]
    private string $accessToken;

    #[ORM\Column(type: Types::STRING, length: 127)]
    private string $refreshToken;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $accessTokenExpiresAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $refreshTokenExpiresAt;

    #[ORM\ManyToOne(inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getAccessTokenExpiresAt(): \DateTimeInterface
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(\DateTimeInterface $accessTokenExpiresAt): static
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;

        return $this;
    }

    public function getRefreshTokenExpiresAt(): \DateTimeInterface
    {
        return $this->refreshTokenExpiresAt;
    }

    public function setRefreshTokenExpiresAt(\DateTimeInterface $refreshTokenExpiresAt): static
    {
        $this->refreshTokenExpiresAt = $refreshTokenExpiresAt;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
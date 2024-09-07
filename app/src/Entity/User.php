<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue('SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 63)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 63)]
    private string $surname;

    #[ORM\Column(type: Types::STRING, length: 63)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 63)]
    private string $role;

    /**
     * @var Collection<int, MagicLinkToken>
     */
    #[ORM\OneToMany(targetEntity: MagicLinkToken::class, mappedBy: 'owner')]
    private Collection $magicLinkTokens;

    public function __construct()
    {
        $this->magicLinkTokens = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, MagicLinkToken>
     */
    public function getMagicLinkTokens(): Collection
    {
        return $this->magicLinkTokens;
    }

    public function addMagicLinkToken(MagicLinkToken $magicLinkToken): static
    {
        if (!$this->magicLinkTokens->contains($magicLinkToken)) {
            $this->magicLinkTokens->add($magicLinkToken);
            $magicLinkToken->setOwner($this);
        }

        return $this;
    }

    public function removeMagicLinkToken(MagicLinkToken $magicLinkToken): static
    {
        if ($this->magicLinkTokens->removeElement($magicLinkToken)) {
            // set the owning side to null (unless already changed)
            if ($magicLinkToken->getOwner() === $this) {
                $magicLinkToken->setOwner(null);
            }
        }

        return $this;
    }
}
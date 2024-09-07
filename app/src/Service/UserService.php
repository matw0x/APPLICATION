<?php

namespace App\Service;

use App\Entity\MagicLinkToken;
use App\Helper\Enum\MagicLinkTokenStatus;
use App\Repository\UserRepository;
use App\Service\MagicLink\MagicLinkService;
use App\Service\Mailer\YandexMailerService;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserService
{
    function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function getUsers(): array
    {
        $users = $this->userRepository->findAll();
        $result = [];
        foreach ($users as $user) {
            $result[] = $user->getName();
        }

        return $result;
    }

    public function register(string $email, MagicLinkService $magicLinkService, YandexMailerService $mailerService): void
    {
        $token = $magicLinkService->generateToken();
        $magicLink = $magicLinkService->createMagicLink($token);

        $magicLinkToken = (new MagicLinkToken())
            ->setToken($token)
            ->setStatus(MagicLinkTokenStatus::IS_ACTIVE->value)
            ->setExpiresAt(new \DateTimeImmutable('+30 minutes'));

        $this->entityManager->persist($magicLinkToken);
        $this->entityManager->flush();

        $mailerService->sendMagicLink($email, $magicLink);
    }

    public function verify(): bool
    {


        return true;
    }
}
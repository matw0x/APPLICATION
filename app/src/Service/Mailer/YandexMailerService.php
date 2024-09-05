<?php

namespace App\Service\Mailer;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

readonly class YandexMailerService
{
    function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM')]
        private string          $from,
    )
    {
    }

    public function sendMagicLink(string $to, string $magicLink): void
    {
        $email = (new Email())
            ->from($this->from)
            ->subject('Магическая ссылка для регистрации!')
            ->to($to)
            ->text(sprintf('Перейдите по ссылке для входа: %s', $magicLink));

        $this->mailer->send($email);
    }
}
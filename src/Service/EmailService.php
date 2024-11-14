<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    public function sendEmail(
        string $to,
        string $subject,
        string $htmlContent,
        string $from = 'onboarding@resend.dev'
    ): void {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}

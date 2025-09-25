<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'extranet@yourspain.es',
        private string $fromName = 'Extranet System'
    ) {}

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetToken): void
    {
        $resetUrl = "http://localhost:3000/#/reset-password?token={$resetToken}";
        
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Reset hasła - Extranet System')
            ->html($this->twig->render('emails/password_reset.html.twig', [
                'userName' => $toName,
                'resetUrl' => $resetUrl,
                'expiresIn' => '1 godzina'
            ]));

        $this->mailer->send($email);
    }

    public function sendPasswordResetConfirmation(string $toEmail, string $toName): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Hasło zostało zmienione - Extranet System')
            ->html($this->twig->render('emails/password_reset_confirmation.html.twig', [
                'userName' => $toName
            ]));

        $this->mailer->send($email);
    }
}

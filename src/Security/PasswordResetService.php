<?php

namespace App\Security;

use App\Configuration\SecurityConfig;
use App\Entity\PasswordReset;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createCode(User $user) : string {
        return hash('sha256', $user->getId() . ';' . $user->getEmail() . ';' . $user->getPassword() . ';' . time());
    }

    public function createPasswordReset(User $user) : PasswordReset {
        $code = $this->createCode($user);
        $reset = (new PasswordReset())
            ->setUser($user)
            ->setCode($code)
            ->setSendAt(new \DateTimeImmutable());

        $this->entityManager->persist($reset);
        $this->entityManager->flush();

        return $reset;
    }

    public function sendEmailReset(User $user) : bool {
        $reset = $this->createPasswordReset($user);
        return $this->send($user, $reset);
    }

    public function send(User $user, PasswordReset $passwordReset = null) : bool {
        $email = (new TemplatedEmail())
            ->from(new Address(SecurityConfig::MAILER_MAIL, SecurityConfig::MAILER_NAME))
            ->to($user->getEmail())
            ->subject(SecurityConfig::MAILER_SUBJECT_RESET)
            ->htmlTemplate('password/reset/password_reset_template.html.twig');

        $url = $this->urlGenerator->generate('app_password_reset_response', [
            'code' => ($passwordReset ? $passwordReset->getCode() : $user->getPasswordReset()->getCode()),
            'user' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = $email->getContext();
        $context['resetUrl'] = $url;

        $email->context($context);

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }

}

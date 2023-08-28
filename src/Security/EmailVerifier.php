<?php

namespace App\Security;

use App\Configuration\SecurityConfig;
use App\Entity\EmailVerification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerifier
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

    public function createEmailVerification(User $user) : EmailVerification {
        $code = $this->createCode($user);
        $email = (new EmailVerification())
            ->setUser($user)
            ->setCode($code)
            ->setSendAt(new \DateTimeImmutable());

        $this->entityManager->persist($email);
        $this->entityManager->flush();

        return $email;
    }

    public function sendEmailVerification(User $user) : bool {
        $email = $this->createEmailVerification($user);
        return $this->send($user, $email);
    }

    public function send(User $user, EmailVerification $emailVerification = null) : bool {
        $email = (new TemplatedEmail())
            ->from(new Address(SecurityConfig::MAILER_MAIL, SecurityConfig::MAILER_NAME))
            ->to($user->getEmail())
            ->subject(SecurityConfig::MAILER_SUBJECT)
            ->htmlTemplate('registration/confirmation_email.html.twig');

        $url = $this->urlGenerator->generate('app_verify_email', [
            'code' => ($emailVerification ? $emailVerification->getCode() : $user->getEmailVerification()->getCode()),
            'user' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = $email->getContext();
        $context['verifyUrl'] = $url;

        $email->context($context);

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }

}

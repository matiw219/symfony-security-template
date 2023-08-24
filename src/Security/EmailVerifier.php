<?php

namespace App\Security;

use App\Configuration\SecurityConfig;
use App\Entity\EmailVerification;
use App\Entity\User;
use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerifier
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private EmailVerificationRepository $emailVerificationRepository,
    ) {
    }

    public function createEmailVerification(User $user) : EmailVerification {
        $code = hash('sha256', $user->getId() . ';' . $user->getEmail() . ';' . $user->getPassword() . ';' . time());
        $email = (new EmailVerification())
            ->setUser($user)
            ->setCode($code)
            ->setSendAt(new \DateTimeImmutable());

        $this->entityManager->persist($email);
        $this->entityManager->flush();

        return $email;
    }

    public function sendEmailVerification(User $user) : void {
        $emailVerification = $this->createEmailVerification($user);
        $email = (new TemplatedEmail())
            ->from(new Address(SecurityConfig::MAILER_MAIL, SecurityConfig::MAILER_NAME))
            ->to($user->getEmail())
            ->subject(SecurityConfig::MAILER_SUBJECT)
            ->htmlTemplate('registration/confirmation_email.html.twig');

        $url = $this->urlGenerator->generate('app_verify_email', [
            'code' => $emailVerification->getCode(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = $email->getContext();
        $context['verifyUrl'] = $url;

        $email->context($context);

        $this->mailer->send($email);
    }

}

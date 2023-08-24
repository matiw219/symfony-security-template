<?php

namespace App\Security;

use App\Entity\EmailVerification;
use App\Entity\User;
use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
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

}

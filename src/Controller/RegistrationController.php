<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\AppAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly EntityManagerInterface $entityManager,
    ){}

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppAuthenticator $authenticator): Response
    {
        if ($this->getUser()) {
            $this->addFlash('error', 'You are logged in');
            return $this->redirectToRoute('app_index');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->emailVerifier->sendEmailVerification($user);

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $code = $request->query->get('code', '');
        $username = $request->query->get('user');

        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            $this->addFlash('error', 'You are not logged in');
            return $this->redirectToRoute('app_register');
        }

        $email = $user->getEmailVerification();
        if (!$email || $email->getCode() != $code) {
            $this->addFlash('error', 'An unexpected error occurred VerifyEmail');
            return $this->redirectToRoute('app_index');
        }

        $user->setIsVerified(true);

        $this->entityManager->remove($email);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_index');
    }

    #[Route('/resend/email', name: 'app_resend_email')]
    public function reSendEmail() : JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['can' => false]);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['can' => false]);
        }

        if (!$user->getEmailVerification()) {
            return new JsonResponse(['can' => false]);
        }

        $current = new \DateTimeImmutable();
        $interval = $current->diff($user->getEmailVerification()->getSendAt());

        if ($interval->i >= 1 || $interval->h >= 1) {
            $email = $user->getEmailVerification();
            $email->setSendAt(new \DateTimeImmutable());
            $email->setCode($this->emailVerifier->createCode($user));
            $this->entityManager->flush();

            $this->emailVerifier->send($user);

            return new JsonResponse(['can' => true]);
        }
        return new JsonResponse(['can' => false]);
    }
}

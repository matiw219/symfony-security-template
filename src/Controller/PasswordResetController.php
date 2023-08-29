<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordResetFormType;
use App\Form\PasswordResetRequestFormType;
use App\Repository\UserRepository;
use App\Security\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/password-reset', name: 'app_password_reset_request')]
    public function passwordRequest(Request $request, UserRepository $userRepository, PasswordResetService $passwordResetService, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('app_index');
        }

        $passwordResetForm = $this->createForm(PasswordResetRequestFormType::class);
        $passwordResetForm->handleRequest($request);

        if ($passwordResetForm->isSubmitted() && $passwordResetForm->isValid()) {
            $email = $passwordResetForm->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $passwordResetForm->get('email')->addError(
                    new FormError('User not found with this email address')
                );
            }
            else {
                if ($user->getPasswordReset()) {
                    $current = new \DateTimeImmutable();
                    $interval = $current->diff($user->getPasswordReset()->getSendAt());
                    if ($interval->i < 1 && $interval->h < 1) {
                        $this->addFlash('error', 'Email has been sent, if it didnt arrive wait a minute and resend it');
                        return $this->render('registration/password_reset_request.html.twig', [
                            'passwordResetForm' => $passwordResetForm->createView()
                        ]);
                    }
                    else {
                        $user->getPasswordReset()->setCode($passwordResetService->createCode($user));
                        $user->getPasswordReset()->setSendAt(new \DateTimeImmutable());

                        $entityManager->flush();
                    }
                    $passwordResetService->send($user);
                }
                else {
                    $passwordResetService->sendEmailReset($user);
                }
                $this->addFlash('success', 'An email with a link has been sent');

            }
        }

        return $this->render('registration/password_reset_request.html.twig', [
            'passwordResetForm' => $passwordResetForm->createView()
        ]);
    }

    #[Route('/password-reset-response', name: 'app_password_reset_response')]
    public function passwordResponse(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('app_index');
        }

        $code = $request->get('code', '');
        $username = $request->get('user');

        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        $passwordReset = $user->getPasswordReset();

        if (!$passwordReset || $passwordReset->getCode() != $code) {
            return $this->redirectToRoute('app_index');
        }

        $resetPasswordForm = $this->createForm(PasswordResetFormType::class);
        $resetPasswordForm->handleRequest($request);

        if ($resetPasswordForm->isSubmitted() && $resetPasswordForm->isValid()) {
            $password = $resetPasswordForm->get('newPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $password));
            $entityManager->remove($user->getPasswordReset());
            $entityManager->flush();

            $this->addFlash('success', 'Password has been changed');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/password_reset_response.html.twig', [
            'resetPasswordForm' => $resetPasswordForm->createView()
        ]);
    }
}

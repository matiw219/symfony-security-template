<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordResetRequestFormType;
use App\Repository\UserRepository;
use App\Security\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/password-reset', name: 'app_password_reset_request')]
    public function passwordRequest(Request $request, UserRepository $userRepository, PasswordResetService $passwordResetService): Response
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
                }
                $this->addFlash('success', 'An email with a link has been sent');
                $passwordResetService->sendEmailReset($user);
            }
        }

        return $this->render('registration/password_reset_request.html.twig', [
            'passwordResetForm' => $passwordResetForm->createView()
        ]);
    }

    #[Route('/password-reset-response', name: 'app_password_reset_response')]
    public function passwordResponse(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('app_index');
        }

        return $this->render('registration/password_reset_response.html.twig', [
        ]);
    }
}

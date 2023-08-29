<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ChangePasswordController extends AbstractController
{
    #[Route('/change-password', name: 'app_change_password')]
    public function passwordRequest(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Zaloguj sie');
            return $this->redirectToRoute('app_login');
        }

        $changePasswordForm = $this->createForm(ChangePasswordFormType::class);
        $changePasswordForm->handleRequest($request);
        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $old = $changePasswordForm->get('oldPassword')->getData();
            $new = $changePasswordForm->get('newPassword')->getData();

            if (!$userPasswordHasher->isPasswordValid($user, $old)) {
                $changePasswordForm->get('oldPassword')->addError(new FormError('The old password provided is wrong'));
            }
            else {
                $user->setPassword($userPasswordHasher->hashPassword($user, $new));
                $entityManager->flush();
                $this->addFlash('success', 'Password has been changed');
                return $this->redirectToRoute('app_index');
            }
        }

        return $this->render('password/change/password_change.html.twig', [
            'changePasswordForm' => $changePasswordForm->createView()
        ]);
    }
}

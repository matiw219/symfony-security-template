<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            if (!$user->isVerified()) {
                return $this->render('registration/please_confirm_your_email.html.twig');
            }
        }
        return $this->render('index/index.html.twig', []);
    }
}

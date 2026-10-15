<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\DevLoginAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Formulaire de connexion de développement (dev uniquement). En prod : SSO Microsoft.
 * La soumission (POST) est traitée par App\Security\DevLoginAuthenticator.
 */
#[When(env: 'dev')]
final class DevLoginController extends AbstractController
{
    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'], env: 'dev')]
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/dev_login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'username' => DevLoginAuthenticator::USERNAME,
            'password' => DevLoginAuthenticator::PASSWORD,
        ]);
    }
}

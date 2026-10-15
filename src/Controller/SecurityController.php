<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\AzureSsoConfiguration;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Connexion SSO Microsoft, désactivée en dev (paramètre app.sso_enabled).
 * La déconnexion (/deconnexion) est gérée par le pare-feu (config/packages/security.yaml).
 */
final class SecurityController extends AbstractController
{
    #[Route('/connect/azure', name: 'connect_azure_start', methods: ['GET'])]
    public function connect(ClientRegistry $clientRegistry, AzureSsoConfiguration $configuration): Response
    {
        if (!$configuration->enabled) {
            throw $this->createNotFoundException('SSO désactivé dans cet environnement.');
        }

        if (!$configuration->isConfigured()) {
            return $this->render('security/login_failure.html.twig', [
                'message' => 'La connexion Microsoft n’est pas encore configurée pour cette application.',
            ], new Response(status: Response::HTTP_SERVICE_UNAVAILABLE));
        }

        return $clientRegistry->getClient('azure')->redirect(['openid', 'profile', 'email'], []);
    }

    /**
     * Retour de Microsoft : intercepté par App\Security\AzureAuthenticator avant d'arriver ici.
     */
    #[Route('/connect/azure/check', name: 'connect_azure_check', methods: ['GET'])]
    public function check(AzureSsoConfiguration $configuration): never
    {
        if (!$configuration->enabled) {
            throw $this->createNotFoundException('SSO désactivé dans cet environnement.');
        }

        throw new \LogicException('Cette route est interceptée par App\Security\AzureAuthenticator.');
    }
}

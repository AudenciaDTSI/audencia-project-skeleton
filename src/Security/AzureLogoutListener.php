<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Après la déconnexion de l'application, déconnecte aussi la session Microsoft :
 * sinon la personne serait reconnectée automatiquement à la visite suivante.
 */
#[AsEventListener(event: LogoutEvent::class, dispatcher: 'security.event_dispatcher.main')]
final readonly class AzureLogoutListener
{
    public function __construct(
        private AzureSsoConfiguration $configuration,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        if (!$this->configuration->enabled) {
            return;
        }

        $event->setResponse(new RedirectResponse(\sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/logout?%s',
            rawurlencode($this->configuration->tenantId),
            http_build_query([
                'post_logout_redirect_uri' => $this->urlGenerator->generate('connect_azure_start', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]),
        )));
    }
}

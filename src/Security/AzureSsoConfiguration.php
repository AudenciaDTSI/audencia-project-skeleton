<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Paramètres de l'application Entra ID (SSO Microsoft), lus dans les variables d'environnement.
 */
final readonly class AzureSsoConfiguration
{
    /** Tenants multi-organisations : aucun contrôle d'appartenance possible, donc refusés. */
    private const array SHARED_TENANTS = ['common', 'organizations', 'consumers'];

    public function __construct(
        #[Autowire(env: 'AZURE_CLIENT_ID')]
        private string $clientId,
        #[Autowire(env: 'AZURE_CLIENT_SECRET')]
        private string $clientSecret,
        #[Autowire(env: 'AZURE_TENANT_ID')]
        public string $tenantId,
        /** Faux en dev : connexion par formulaire audencia / audencia (config/services.yaml). */
        #[Autowire(param: 'app.sso_enabled')]
        public bool $enabled = true,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->clientId)
            && '' !== trim($this->clientSecret)
            && '' !== trim($this->tenantId)
            && !\in_array(strtolower($this->tenantId), self::SHARED_TENANTS, true);
    }

    public function belongsToTenant(?string $tenantId): bool
    {
        return $this->isConfigured()
            && null !== $tenantId
            && 0 === strcasecmp($tenantId, $this->tenantId);
    }
}

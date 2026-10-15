<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AzureLogoutListener;
use App\Security\AzureSsoConfiguration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class AzureLogoutListenerTest extends TestCase
{
    private const string TENANT = '7a879e87-0000-0000-0000-000000000000';

    #[Test]
    public function signsOutOfMicrosoftWhenSsoIsEnabled(): void
    {
        $event = new LogoutEvent(new Request(), null);

        $this->listener(enabled: true)($event);

        self::assertSame(
            'https://login.microsoftonline.com/'.self::TENANT.'/oauth2/v2.0/logout?post_logout_redirect_uri='.urlencode('https://app.audencia.com/connect/azure'),
            $event->getResponse()?->headers->get('Location'),
        );
    }

    #[Test]
    public function leavesTheDefaultLogoutWhenSsoIsDisabled(): void
    {
        $event = new LogoutEvent(new Request(), null);

        $this->listener(enabled: false)($event);

        self::assertNull($event->getResponse());
    }

    private function listener(bool $enabled): AzureLogoutListener
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://app.audencia.com/connect/azure');

        return new AzureLogoutListener(new AzureSsoConfiguration('client', 'secret', self::TENANT, $enabled), $urlGenerator);
    }
}

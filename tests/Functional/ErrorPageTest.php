<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

final class ErrorPageTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function statusCodes(): iterable
    {
        yield 'mapped client error' => [404, 'Not Found', 'Page introuvable'];
        yield 'mapped server error' => [503, 'Service Unavailable', 'Service indisponible'];
        yield 'unmapped server error' => [502, 'Bad Gateway', 'Erreur interne'];
        yield 'unmapped client error' => [418, 'I\'m a teapot', 'I&#039;m a teapot'];
    }

    #[Test]
    #[DataProvider('statusCodes')]
    public function rendersTitleForStatusCode(int $code, string $text, string $expectedTitle): void
    {
        $twig = self::getContainer()->get(Environment::class);

        $html = $twig->render('@Twig/Exception/error.html.twig', [
            'status_code' => $code,
            'status_text' => $text,
            'exception' => new \RuntimeException('secret interne'),
        ]);

        self::assertStringContainsString('<h1>'.$expectedTitle.'</h1>', $html);
        self::assertStringContainsString('Erreur '.$code, $html);
        self::assertStringNotContainsString('secret interne', $html);
    }
}

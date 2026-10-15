<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/../.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0o000);
}

// Base de test reconstruite à chaque lancement à partir des migrations.
foreach (['doctrine:schema:drop --force --full-database', 'doctrine:migrations:migrate --no-interaction'] as $command) {
    passthru(\sprintf('php %s/../bin/console %s --env=test --quiet', __DIR__, $command), $exitCode);

    if (0 !== $exitCode) {
        exit($exitCode);
    }
}

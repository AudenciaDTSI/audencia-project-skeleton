<?php

use App\Kernel;

require_once __DIR__.'/../vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    /** @var string $env */
    $env = $context['APP_ENV'];

    return new Kernel($env, (bool) $context['APP_DEBUG']);
};

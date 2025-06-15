<?php
declare(strict_types=1);

use KnorkFork\LoadEnvironment\Environment;

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
Environment::load(__DIR__ . '/../.env');
$overrideEnvToTest = false;
if (getenv('ALLOW_ENV_OVERRIDE') === 'true') {
    if (isset($_SERVER['HTTP_X_APP_ENV']) && $_SERVER['HTTP_X_APP_ENV'] === 'test') {
        $overrideEnvToTest = true;
    }
}
if ($overrideEnvToTest) {
    Environment::load(__DIR__ . '/../.env', ['test']);
} else {
    Environment::load(__DIR__ . '/../.env');
}

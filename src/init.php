<?php
declare(strict_types=1);

use KnorkFork\LoadEnvironment\Environment;

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
Environment::load(__DIR__ . '/../.env');

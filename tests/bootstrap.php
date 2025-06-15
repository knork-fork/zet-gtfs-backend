<?php
declare(strict_types=1);

use KnorkFork\LoadEnvironment\Environment;

// Enable strict error reporting
error_reporting(\E_ALL);
ini_set('display_errors', '1');

// Load Composer dependencies (autoloader)
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
Environment::load(__DIR__ . '/../.env', ['test']);

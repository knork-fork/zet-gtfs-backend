<?php
declare(strict_types=1);

use App\Router;

require_once __DIR__ . '/../src/init.php';

$uri = $_SERVER['REQUEST_URI'];
$uri = is_string($uri) ? $uri : '';

$response = Router::getResponse($uri);
$response->output();

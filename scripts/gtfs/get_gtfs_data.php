<?php
declare(strict_types=1);

use App\Service\GtfsDataService;

require_once __DIR__ . '/../../src/init.php';

$gtfsDataService = new GtfsDataService();
$gtfsDataService->fetchDataToCache();

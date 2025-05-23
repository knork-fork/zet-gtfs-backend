<?php
declare(strict_types=1);

use App\Service\CachedDataService;
use App\System\Logger;
use KnorkFork\LoadEnvironment\Environment;

require_once __DIR__ . '/../../src/init.php';

$zetUrl = Environment::getStringEnv('ZET_URL');
$cacheDummyData = Environment::getStringEnv('LOAD_DUMMY_DATA') === 'true';

if ($cacheDummyData) {
    // Cache dummy data instead of pinging third-party
    Logger::info('Caching dummy GTFS data', 'gtfs_cron');
    $json = file_get_contents('/application/test/TestData/gtfs_dummy.json');
    file_put_contents(CachedDataService::GTFS_CACHE_FILENAME, $json);

    exit;
}

$json = shell_exec(
    '/opt/venv/bin/python /application/scripts/gtfs/gtfs2json.py '
    . escapeshellarg($zetUrl)
);

if (!is_string($json)) {
    Logger::critical('Error while executing gtfs2json script: ' . $json, 'gtfs_cron');
    throw new Exception('Error while executing gtfs2json script');
}
if (!json_validate($json)) {
    Logger::critical('Invalid GTFS JSON: ' . json_last_error_msg(), 'gtfs_cron');
    throw new Exception('Invalid GTFS JSON');
}

// Received JSON is valid, cache it
file_put_contents(CachedDataService::GTFS_CACHE_FILENAME, $json);
Logger::info(sprintf(
    'GTFS data cached successfully (size: %d)',
    strlen($json)
), 'gtfs_cron');

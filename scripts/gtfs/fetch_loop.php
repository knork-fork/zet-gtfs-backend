<?php
declare(strict_types=1);

use App\System\Logger;
use KnorkFork\LoadEnvironment\Environment;

require_once __DIR__ . '/../../src/init.php';

$pollingInterval = Environment::getStringEnv('POLLING_INTERVAL_IN_SECONDS');
if (!is_numeric($pollingInterval)) {
    Logger::critical('Invalid polling interval: ' . $pollingInterval, 'gtfs_cron');
    throw new Exception('Invalid polling interval');
}
$pollingInterval = (int) $pollingInterval;

// @phpstan-ignore-next-line While loop condition is always true
while (true) {
    shell_exec('php /application/scripts/gtfs/get_gtfs_data.php');
    sleep($pollingInterval);
}

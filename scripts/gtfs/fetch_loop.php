<?php
declare(strict_types=1);

use App\Service\CachedDataService;
use App\System\Logger;
use KnorkFork\LoadEnvironment\Environment;

require_once __DIR__ . '/../../src/init.php';

// Cache frontend and backend commit hashes
$frontendCommit = shell_exec(
    'git -c safe.directory=/application/frontend -C /application/frontend rev-parse HEAD 2>&1'
);
$backendCommit = shell_exec(
    'git -c safe.directory=/application -C /application rev-parse HEAD 2>&1'
);
file_put_contents(CachedDataService::FRONTEND_COMMIT_FILENAME, $frontendCommit);
file_put_contents(CachedDataService::BACKEND_COMMIT_FILENAME, $backendCommit);

$pollingInterval = Environment::getStringEnv('POLLING_INTERVAL_IN_SECONDS');
if (!is_numeric($pollingInterval)) {
    Logger::critical('Invalid polling interval: ' . $pollingInterval, 'gtfs_cron');
    throw new Exception('Invalid polling interval');
}
$pollingInterval = (int) $pollingInterval;
$inactivityTime = Environment::getStringEnv('STOP_POLLING_AFTER_INACTIVITY_IN_SECONDS');
if (!is_numeric($inactivityTime)) {
    Logger::critical('Invalid inactivity time: ' . $inactivityTime, 'gtfs_cron');
    throw new Exception('Invalid inactivity time');
}
$inactivityTime = (int) $inactivityTime;

$shouldLogPollingStatus = true;
// @phpstan-ignore-next-line While loop condition is always true
while (true) {
    if (shouldPollData($inactivityTime)) {
        $shouldLogPollingStatus = true;
        shell_exec('php /application/scripts/gtfs/get_gtfs_data.php');
    } else {
        if ($shouldLogPollingStatus) {
            Logger::info('Inactivity detected, polling stopped', 'gtfs_cron');
            $shouldLogPollingStatus = false;
        }
    }

    sleep($pollingInterval);
}

function shouldPollData(int $inactivityTime): bool
{
    if (!file_exists(CachedDataService::LAST_CACHE_READ_FILENAME)) {
        // Cache was never read, do not poll
        return false;
    }

    // Clear internal stat cache
    clearstatcache(true, CachedDataService::LAST_CACHE_READ_FILENAME);

    $lastCacheRead = filemtime(CachedDataService::LAST_CACHE_READ_FILENAME);
    if ($lastCacheRead === false) {
        return false;
    }

    $timeSinceLastRead = time() - $lastCacheRead;
    if ($timeSinceLastRead > $inactivityTime) {
        // Cache was not read for a while, do not poll
        return false;
    }

    return true;
}

<?php
declare(strict_types=1);

/**
 * This script is ran in a loop in zet-gtfs-php-fetcher container.
 * It checks if the GTFS data cache was read recently and if so, it fetches new GTFS data.
 * If the cache was not read for a while, it stops polling.
 * The polling interval and inactivity time can be configured via environment variables.
 */

use App\Service\AppVersionService;
use App\Service\BajsDataService;
use App\Service\CachedDataService;
use App\System\Logger;
use KnorkFork\LoadEnvironment\Environment;

require_once __DIR__ . '/../../src/init.php';

// fetch_loop.php is ran on startup so this is a good place to save git info to cache
AppVersionService::addVersionInfoToCache();

$pollingInterval = Environment::getStringEnv('POLLING_INTERVAL_IN_SECONDS');
if (!is_numeric($pollingInterval)) {
    Logger::critical('Invalid polling interval: ' . $pollingInterval, 'gtfs_cron');
    throw new Exception('Invalid polling interval');
}
$pollingInterval = (int) $pollingInterval;

$bajsPollingMultiplier = Environment::getStringEnv('BAJS_POLLING_MULTIPLIER');
if (!is_numeric($bajsPollingMultiplier)) {
    Logger::critical('Invalid BAJS polling multiplier: ' . $bajsPollingMultiplier, 'gtfs_cron');
    throw new Exception('Invalid BAJS polling multiplier');
}
$bajsPollingMultiplier = (int) $bajsPollingMultiplier;

$inactivityTime = Environment::getStringEnv('STOP_POLLING_AFTER_INACTIVITY_IN_SECONDS');
if (!is_numeric($inactivityTime)) {
    Logger::critical('Invalid inactivity time: ' . $inactivityTime, 'gtfs_cron');
    throw new Exception('Invalid inactivity time');
}
$inactivityTime = (int) $inactivityTime;

$bajsLoopCounter = 0;

$shouldLogPollingStatus = true;
// @phpstan-ignore-next-line While loop condition is always true
while (true) {
    if (shouldPollData($inactivityTime)) {
        $shouldLogPollingStatus = true;
        shell_exec('php /application/scripts/gtfs/get_gtfs_data.php');

        // Because we don't have separate cron for bajs data, we can poll it every Nth loop of fetching GTFS data.
        // This way we can reduce the load on bajs API and still have relatively fresh data.
        if ($bajsPollingMultiplier > 0) {
            ++$bajsLoopCounter;
            if ($bajsLoopCounter === $bajsPollingMultiplier) {
                (new BajsDataService())->fetchDataToCache();

                $bajsLoopCounter = 0;
            }
        }
    } else {
        if ($shouldLogPollingStatus) {
            Logger::info('Inactivity detected, polling stopped', 'gtfs_cron');
            $shouldLogPollingStatus = false;

            // Ensure bajs data is fetched on the first loop when polling resumes
            $bajsLoopCounter = $bajsPollingMultiplier - 1;
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

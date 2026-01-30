<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BadRequestException;
use App\Repository\VehicleRepository;
use App\System\Logger;
use Exception;
use KnorkFork\LoadEnvironment\Environment;

final class GtfsDataService
{
    public const GTFS_CACHE_FILENAME = '/application/var/cache/latest_gtfs.json';

    /**
     * @throws BadRequestException
     */
    public function fetchDataToCacheIfOutdated(): void
    {
        if (!$this->isCacheOutdated()) {
            // Cache is still fresh, no need to fetch data
            return;
        }

        Logger::info('GTFS cache is outdated because polling stopped, fetching data.', 'gtfs_cron');

        try {
            $this->fetchDataToCache();
        } catch (Exception $e) {
            Logger::error('Keeping outdated json, error while fetching GTFS data: ' . $e->getMessage(), 'gtfs_cron');
        }
    }

    /**
     * @throws Exception
     */
    public function fetchDataToCache(): void
    {
        $timeNow = microtime(true);
        $cacheDummyData = Environment::getStringEnv('LOAD_DUMMY_DATA') === 'true';
        if ($cacheDummyData) {
            // Cache dummy data instead of pinging third-party
            Logger::info('Caching dummy GTFS data', 'gtfs_cron');
            $this->fetchDummyData();
        } else {
            // Fetch and cache real-time data from ZET GTFS source
            $this->fetchRealtimeData();
        }
        $fetchingTime = microtime(true) - $timeNow;

        $timeNow = microtime(true);
        $vehicleRepository = new VehicleRepository();
        $cachedDataService = new CachedDataService();
        $vehicleDataService = new VehicleDataService(
            $vehicleRepository,
            $cachedDataService,
        );
        $vehicleDataService->saveVehicleDataToDb();
        $processingTime = microtime(true) - $timeNow;

        $fetchDuration = $fetchingTime + $processingTime;
        Logger::info(\sprintf(
            'GTFS data fetch and cache completed in %.2f seconds (%.2f fetching, %.2f processing)',
            $fetchDuration,
            $fetchingTime,
            $processingTime,
        ), 'gtfs_cron');
    }

    private function fetchDummyData(): void
    {
        $json = file_get_contents('/application/tests/TestData/gtfs_dummy.json');
        file_put_contents(self::GTFS_CACHE_FILENAME, $json);
    }

    private function fetchRealtimeData(): void
    {
        $zetUrl = Environment::getStringEnv('ZET_URL');
        $json = shell_exec(
            '/opt/venv/bin/python /application/scripts/gtfs/gtfs2json.py '
            . escapeshellarg($zetUrl) . ' 2>&1'
        );

        if (!\is_string($json)) {
            Logger::critical('Unknown error while executing gtfs2json script', 'gtfs_cron');
            throw new Exception('Error while executing gtfs2json script');
        }

        if (!json_validate($json) || !str_contains($json, 'gtfsRealtimeVersion')) {
            $errorMsg = \sprintf(
                'Invalid GTFS JSON: %s, shell output: %s',
                json_last_error_msg(),
                // safety limit to not overwhelm the log
                substr($json, 0, 400)
            );
            Logger::critical($errorMsg, 'gtfs_cron');
            throw new Exception('Invalid GTFS JSON');
        }

        // Received JSON is valid, cache it
        file_put_contents(self::GTFS_CACHE_FILENAME, $json);
        Logger::info(\sprintf(
            'GTFS data cached successfully (size: %d)',
            \strlen($json)
        ), 'gtfs_cron');
    }

    private function isCacheOutdated(): bool
    {
        $inactivityTime = (int) Environment::getStringEnv('STOP_POLLING_AFTER_INACTIVITY_IN_SECONDS');

        if (!file_exists(self::GTFS_CACHE_FILENAME)) {
            return true;
        }

        $lastCacheWrite = filemtime(self::GTFS_CACHE_FILENAME);
        if ($lastCacheWrite === false) {
            return true;
        }

        // Cache is outdated if older than inactivity time
        return (time() - $lastCacheWrite) >= $inactivityTime;
    }
}

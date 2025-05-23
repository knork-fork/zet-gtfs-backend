<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
use App\Service\AppVersionService;
use App\Service\CachedDataService;
use KnorkFork\LoadEnvironment\Environment;

final class StatusController
{
    public static function status(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    public static function info(): JsonResponse
    {
        $pollInterval = Environment::getStringEnv('POLLING_INTERVAL_IN_SECONDS');
        $inactivityTime = Environment::getStringEnv('STOP_POLLING_AFTER_INACTIVITY_IN_SECONDS');
        $lastCacheRead = filemtime(CachedDataService::LAST_CACHE_READ_FILENAME);
        $lastCacheWrite = filemtime(CachedDataService::GTFS_CACHE_FILENAME);
        $zetUrl = Environment::getStringEnv('ZET_URL');
        $appVersionInfo = AppVersionService::getVersionInfoFromCache();

        $isCurrentlyPolling = (time() - $lastCacheRead) < $inactivityTime;

        return new JsonResponse([
            'description' => 'GTFS-RT to JSON converter',
            'zet_url' => $zetUrl,
            'polling_interval_in_seconds' => $pollInterval,
            'stop_polling_after_inactivity_in_seconds' => $inactivityTime,
            'last_cache_read' => $lastCacheRead,
            'last_cache_write' => $lastCacheWrite,
            'is_currently_polling' => $isCurrentlyPolling,
            'frontend_version' => $appVersionInfo['checkedOutFrontendRef'],
            'backend_version' => $appVersionInfo['checkedOutBackendRef'],
            'should_update' => $appVersionInfo['should_update'],
        ]);
    }
}

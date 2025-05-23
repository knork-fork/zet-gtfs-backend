<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
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
        $checkedOutFrontendRef = file_get_contents(CachedDataService::FRONTEND_COMMIT_FILENAME);
        $checkedOutBackendRef = file_get_contents(CachedDataService::BACKEND_COMMIT_FILENAME);

        $isCurrentlyPolling = (time() - $lastCacheRead) < $inactivityTime;

        return new JsonResponse([
            'description' => 'GTFS-RT to JSON converter',
            'zet_url' => $zetUrl,
            'polling_interval_in_seconds' => $pollInterval,
            'stop_polling_after_inactivity_in_seconds' => $inactivityTime,
            'last_cache_read' => $lastCacheRead,
            'last_cache_write' => $lastCacheWrite,
            'is_currently_polling' => $isCurrentlyPolling,
            'frontend_version' => $checkedOutFrontendRef,
            'backend_version' => $checkedOutBackendRef,
        ]);
    }
}

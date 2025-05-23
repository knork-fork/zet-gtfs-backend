<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
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
        $cronPollInterval = Environment::getStringEnv('CRON_POLLING_INTERVAL_IN_SECONDS');
        $zetUrl = Environment::getStringEnv('ZET_URL');

        return new JsonResponse([
            'description' => 'GTFS-RT to JSON converter',
            'polling_interval_in_seconds' => $cronPollInterval,
            'zet_url' => $zetUrl,
        ]);
    }
}

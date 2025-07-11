<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
use App\Service\CachedDataService;

final class DataController
{
    public static function getAllData(): JsonResponse
    {
        $cachedDataService = new CachedDataService();
        $cachedData = $cachedDataService->getFullDataFromCache();

        return new JsonResponse($cachedData);
    }

    public static function getAllVehicles(): JsonResponse
    {
    }

    public static function getAllTripUpdates(): JsonResponse
    {
    }

    public static function getAllAlerts(): JsonResponse
    {
    }
}

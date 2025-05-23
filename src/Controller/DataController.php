<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Response\JsonResponse;
use App\Service\CachedDataService;

final class DataController
{
    public static function getAllData(): JsonResponse
    {
        $cachedDataService = new CachedDataService();
        $cachedData = $cachedDataService->getFullDataFromCache();

        if ($cachedData === null) {
            throw new BadRequestException('No cached GTFS data available');
        }

        return new JsonResponse($cachedData);
    }
}

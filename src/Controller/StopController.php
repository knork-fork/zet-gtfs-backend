<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\StopTimeRepository;
use App\Response\JsonResponse;
use App\Service\ArrivalsService;
use App\Service\CachedDataService;

final class StopController
{
    public static function getArrivals(string $stopId): JsonResponse
    {
        // to-do: dependency injection
        $stopTimeRepository = new StopTimeRepository();
        $cachedDataService = new CachedDataService();
        $arrivalsService = new ArrivalsService(
            $stopTimeRepository,
            $cachedDataService
        );

        return new JsonResponse(
            $arrivalsService->getArrivalsForStation($stopId),
        );
    }
}

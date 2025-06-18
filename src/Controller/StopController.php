<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\StopRepository;
use App\Repository\StopTimeRepository;
use App\Response\JsonResponse;
use App\Service\ArrivalsService;
use App\Service\CachedDataService;
use App\Service\CalendarPrefixService;

final class StopController
{
    public static function getArrivals(string $stopId): JsonResponse
    {
        // to-do: dependency injection
        $stopTimeRepository = new StopTimeRepository();
        $cachedDataService = new CachedDataService();
        $calendarPrefixService = new CalendarPrefixService();
        $stopRepository = new StopRepository();
        $arrivalsService = new ArrivalsService(
            $stopTimeRepository,
            $cachedDataService,
            $calendarPrefixService,
            $stopRepository,
        );

        return new JsonResponse(
            $arrivalsService->getArrivalsForStation($stopId),
        );
    }
}

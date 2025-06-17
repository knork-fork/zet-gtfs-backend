<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\StopTimeRepository;
use App\Response\JsonResponse;
use App\Service\ArrivalsService;

final class StopController
{
    public static function getArrivals(string $stopId): JsonResponse
    {
        // to-do: dependency injection
        $stopTimeRepository = new StopTimeRepository();
        $arrivalsService = new ArrivalsService($stopTimeRepository);

        return new JsonResponse(
            $arrivalsService->getArrivalsForStation($stopId),
        );
    }
}

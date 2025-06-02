<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
use App\Service\ArrivalsService;

final class StopController
{
    public static function getArrivals(string $stopId): JsonResponse
    {
        return new JsonResponse(
            ArrivalsService::getArrivalsForStation($stopId),
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Response\JsonResponse;
use App\Service\CachedDataService;
use App\Service\VehicleDataService;

final class DataController
{
    public static function getAllData(): JsonResponse
    {
        $cachedDataService = new CachedDataService();
        $cachedData = $cachedDataService->getFullDataFromCache();

        return new JsonResponse($cachedData);
    }

    public static function getVehicleData(): JsonResponse
    {
        $vehicleRepository = new VehicleRepository();
        $cachedDataService = new CachedDataService();
        $vehicleDataService = new VehicleDataService(
            $vehicleRepository,
            $cachedDataService,
        );
        $vehicles = $vehicleDataService->getVehicleDataFromDb();

        return new JsonResponse($vehicles);
    }
}

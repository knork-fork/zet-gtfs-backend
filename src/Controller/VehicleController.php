<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;

final class VehicleController
{
    public static function getVehicles(string $routeId): JsonResponse
    {
        return new JsonResponse([
            'temp_response' => 'getting all vehicles with routeId ' . $routeId . ' not implemented yet',
        ]);
    }
}

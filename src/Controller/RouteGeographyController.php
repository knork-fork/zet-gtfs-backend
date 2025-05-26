<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Response\RawJsonResponse;

final class RouteGeographyController
{
    private const ROUTE_GEOGRAPHY_DIR_FORMAT = '/application/scripts/gtfs/generated_geojson_routes/route_%s.geojson';

    public static function getRouteGeography(string $routeId): RawJsonResponse
    {
        if (!is_numeric($routeId)) {
            throw new BadRequestException('Invalid route ID');
        }

        $file = \sprintf(self::ROUTE_GEOGRAPHY_DIR_FORMAT, $routeId);
        if (!file_exists($file)) {
            throw new BadRequestException('Route geography not found');
        }

        return new RawJsonResponse(
            (string) file_get_contents(\sprintf(self::ROUTE_GEOGRAPHY_DIR_FORMAT, $routeId)),
        );
    }
}

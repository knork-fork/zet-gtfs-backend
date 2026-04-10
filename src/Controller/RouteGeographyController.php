<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Response\RawJsonResponse;

final class RouteGeographyController
{
    private const DYNAMIC_ROUTE_FORMAT = '/application/resources/gtfs/dynamic/routes/route_%s.geojson';
    private const STATIC_ROUTE_FORMAT = '/application/scripts/gtfs/generated_geojson_routes/route_%s.geojson';

    public static function getRouteGeography(string $routeId): RawJsonResponse
    {
        if (!is_numeric($routeId)) {
            throw new BadRequestException('Invalid route ID');
        }

        $dynamicFile = \sprintf(self::DYNAMIC_ROUTE_FORMAT, $routeId);
        $staticFile = \sprintf(self::STATIC_ROUTE_FORMAT, $routeId);

        $file = file_exists($dynamicFile) ? $dynamicFile : $staticFile;
        if (!file_exists($file)) {
            throw new BadRequestException('Route geography not found');
        }

        return new RawJsonResponse(
            (string) file_get_contents($file),
        );
    }
}

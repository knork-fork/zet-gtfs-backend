<?php
declare(strict_types=1);

namespace App\Helper;

final class GeoDistanceHelper
{
    private const EARTH_RADIUS_M = 6371000.0; // Radius of the Earth in meters

    /**
     * Distance in meters between two points on the Earth specified by latitude and longitude.
     */
    public static function getDistanceBetweenPoints(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_M * $c;
    }
}

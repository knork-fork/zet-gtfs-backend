<?php
declare(strict_types=1);

namespace App\Helper;

use App\Entity\Vehicle;

final class VehicleRotationHelper
{
    public static function calculateRotationForVehicle(Vehicle $vehicle, float $previousLat, float $previousLong): void
    {
        if ($vehicle->position_lat === $previousLat && $vehicle->position_long === $previousLong) {
            // Position unchanged, do not update rotation
            return;
        }

        // Calculate bearing between two geographic coordinates
        $lat1 = deg2rad($previousLat);
        $lon1 = deg2rad($previousLong);
        $lat2 = deg2rad($vehicle->position_lat);
        $lon2 = deg2rad($vehicle->position_long);

        $deltaLon = $lon2 - $lon1;

        $y = sin($deltaLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLon);

        $bearing = atan2($y, $x);

        // Convert from radians to degrees and normalize to 0-360 range
        $vehicle->rotation_deg = fmod(rad2deg($bearing) + 360, 360);
    }
}

<?php
declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Vehicle;
use App\Helper\VehicleRotationHelper;
use App\Tests\Common\UnitTestCase;

/**
 * @internal
 */
final class VehicleRotationHelperTest extends UnitTestCase
{
    public function testCalculateRotationForVehicleForUnchangedPositionDoesNotChangeRotation(): void
    {
        $vehicle = new Vehicle();
        $vehicle->hydrate([
            'route_id' => 10,
            'position_lat' => 45.0,
            'position_long' => 15.0,
            'rotation_deg' => 30.0,
        ]);

        $previousLat = 45.0;
        $previousLong = 15.0;
        $originalRotation = $vehicle->rotation_deg;

        VehicleRotationHelper::calculateRotationForVehicle($vehicle, $previousLat, $previousLong);
        self::assertSame($originalRotation, $vehicle->rotation_deg);
    }

    public function testCalculateRotationForVehicleForChangedPositionUpdatesRotation(): void
    {
        $vehicle = new Vehicle();
        $vehicle->hydrate([
            'route_id' => 10,
            'position_lat' => 0.0, // equator latitude
            'position_long' => 15.0,
            'rotation_deg' => 30.0,
        ]);

        $previousLat = 0.0;
        $previousLong = 14.0;
        $originalRotation = $vehicle->rotation_deg;

        VehicleRotationHelper::calculateRotationForVehicle($vehicle, $previousLat, $previousLong);
        self::assertNotSame($originalRotation, $vehicle->rotation_deg);
        self::assertSame(90.0, $vehicle->rotation_deg);
    }
}

<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\VehiclePosition;

use App\Model\GTFS\Entity\RouteTripModel;

final class VehiclePositionModel
{
    /**
     * @param array{latitude: int, longitute: int} $position
     * @param array{id: string }                   $vehicle
     */
    public function __construct(
        public RouteTripModel $trip,
        public array $position,
        public string $timestamp,
        public array $vehicle,
    ) {
    }
}

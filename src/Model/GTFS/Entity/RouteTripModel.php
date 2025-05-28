<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity;

final class RouteTripModel
{
    public function __construct(
        public string $tripId,
        public string $startDate,
        public string $scheduleRelationship,
        public string $routeId,
    ) {
    }
}

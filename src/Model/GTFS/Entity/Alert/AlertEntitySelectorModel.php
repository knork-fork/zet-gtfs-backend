<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\Alert;

use App\Model\GTFS\Entity\RouteTripModel;

final class AlertEntitySelectorModel
{
    public function __construct(
        public ?string $agencyId,
        public ?string $routeId,
        public ?int $routeType,
        public ?string $stopId,
        public ?RouteTripModel $trip
    ) {
    }
}

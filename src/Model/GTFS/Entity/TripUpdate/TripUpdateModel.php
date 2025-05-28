<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\TripUpdate;

use App\Model\GTFS\Entity\RouteTripModel;

final class TripUpdateModel
{
    /**
     * @param StopTimeUpdateModel[] $stopTimeUpdate
     */
    public function __construct(
        public RouteTripModel $trip,
        public array $stopTimeUpdate,
        public string $timestamp,
    ) {
    }
}

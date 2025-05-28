<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\TripUpdate;

final class StopTimeUpdateModel
{
    public function __construct(
        public int $stopSequence,
        public ?StopTimeEventModel $arrival,
        public ?StopTimeEventModel $departure,
        public string $stopId,
        public string $scheduleRelationship,
    ) {
    }
}

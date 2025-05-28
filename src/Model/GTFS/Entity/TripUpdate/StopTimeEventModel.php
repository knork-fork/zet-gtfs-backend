<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\TripUpdate;

final class StopTimeEventModel
{
    public function __construct(
        public int $delay,
        public string $time
    ) {
    }
}

<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\Alert;

final class AlertTimeRangeModel
{
    public function __construct(
        public ?int $start,
        public ?int $end,
    ) {
    }
}

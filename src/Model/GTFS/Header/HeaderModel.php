<?php
declare(strict_types=1);

namespace App\Model\GTFS\Header;

final class HeaderModel
{
    public function __construct(
        public string $gtfsRealtimeVersion,
        public string $incrementality,
        public string $timestamp
    ) {
    }
}

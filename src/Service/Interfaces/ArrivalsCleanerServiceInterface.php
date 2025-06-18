<?php
declare(strict_types=1);

namespace App\Service\Interfaces;

use DateTime;

interface ArrivalsCleanerServiceInterface
{
    /**
     * Cleans up the arrivals data by formatting arrival time and removing arrivals that have been completed
     *
     * @param array<int, array<string, scalar|null>> $arrivals
     *
     * @return array<int, array<string, scalar|null>>
     */
    public function cleanArrivalsForDateTime(array $arrivals, DateTime $dateTime): array;
}

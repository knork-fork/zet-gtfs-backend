<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use App\Entity\StopTime;
use PDOException;
use RuntimeException;

/**
 * @extends AbstractRepositoryInterface<StopTime>
 */
interface StopTimeRepositoryInterface extends AbstractRepositoryInterface
{
    /**
     * @return StopTime[]
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getStopTimesWithArrivalWithinOneHourForStopId(string $stopId, int $timeInSeconds): array;

    /**
     * @param string[] $tripIds
     *
     * @return array<string, string>
     */
    public function getPreviousTripIdsForTrips(array $tripIds): array;
}

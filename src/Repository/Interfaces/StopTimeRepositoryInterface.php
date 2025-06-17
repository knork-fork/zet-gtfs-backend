<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use App\Entity\StopTime;
use PDOException;
use RuntimeException;

interface StopTimeRepositoryInterface
{
    /**
     * @return StopTime[]
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getStopTimesWithArrivalWithinOneHourForStopId(string $stopId, int $timeInSeconds): array;
}

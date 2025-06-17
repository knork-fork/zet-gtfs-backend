<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\StopTime;
use App\Helper\TimeFormatHelper;
use App\Repository\Interfaces\StopTimeRepositoryInterface;

/**
 * @extends AbstractRepository<StopTime>
 */
final class StopTimeRepository extends AbstractRepository implements StopTimeRepositoryInterface
{
    protected function getEntityClass(): string
    {
        return StopTime::class;
    }

    protected function getTableName(): string
    {
        return 'stop_times';
    }

    public function getStopTimesWithArrivalWithinOneHourForStopId(string $stopId, int $timeInSeconds): array
    {
        $stopTimes = $this->getArrayBy('stop_id', $stopId);

        $relevantStopTimes = [];
        foreach ($stopTimes as $stopTime) {
            if (self::isArrivalTimeWithinOneHour((string) $stopTime['arrival_time'], $timeInSeconds)) {
                $stopTimeObject = new StopTime();
                $stopTimeObject->hydrate($stopTime);
                $relevantStopTimes[] = $stopTimeObject;
            }
        }

        return $relevantStopTimes;
    }

    private function isArrivalTimeWithinOneHour(string $arrivalTime, int $currentTimeInSeconds): bool
    {
        $arrivalSeconds = TimeFormatHelper::getSecondsFromTimeString($arrivalTime);
        $diff = abs($currentTimeInSeconds - $arrivalSeconds);

        // Handle wrap-around at midnight (e.g., 23:30 vs. 00:10)
        if ($diff > 43200) { // 12 hours in seconds
            $diff = 86400 - $diff; // 86400 = seconds in a day
        }

        return $diff <= 3600; // 3600 seconds = 1 hour
    }
}

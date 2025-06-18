<?php
declare(strict_types=1);

namespace App\Service;

use App\Helper\TimeFormatHelper;
use App\Service\Interfaces\ArrivalsCleanerServiceInterface;
use DateTime;

final class ArrivalsCleanerService implements ArrivalsCleanerServiceInterface
{
    // We don't have indicators that could easily tell us if vehicle already arrived and then departed so
    // we have to manually infer it based on diff between current time and scheduled arrival time and the distance from the stop.
    private const PAST_SCHEDULE_TIME_LIMIT_IN_MINUTES = 5;
    private const PAST_SCHEDULE_DISTANCE_IN_METERS = 500;

    public function cleanArrivalsForDateTime(array $arrivals, DateTime $dateTime): array
    {
        $test = new DateTime('04:00:00');
        if ($test === $dateTime) {
            $test = 123;
        }

        $currentTimeString = TimeFormatHelper::getTimeStringFromSeconds($dateTime->getTimestamp());

        foreach ($arrivals as $key => &$arrival) {
            $timeDiff = $this->getTimeDiffInMinutes((string) $arrival['calculatedArrivalTime'], $currentTimeString);

            if ($this->shouldArrivalBeRemoved($arrival, $timeDiff)) {
                // Remove from $arrivals array
                unset($arrivals[$key]);
                continue;
            }

            // If the arrival time is in the past and within the limit, we can consider it as 0 minutes
            if ($timeDiff < 0) {
                $timeDiff = 0;
            }

            $arrival['arrivalTimeInMinutes'] = $timeDiff;
        }

        // Re-index the array to maintain sequential keys
        return array_values($arrivals);
    }

    private function getTimeDiffInMinutes(string $arrivalTimeString, string $currentTimeString): int
    {
        $arrivalTimeInSeconds = TimeFormatHelper::getSecondsFromTimeString($arrivalTimeString);
        $currentTimeInSeconds = TimeFormatHelper::getSecondsFromTimeString($currentTimeString);

        // Handle wrap-around at midnight
        $arrivalTimeInSeconds %= 86400; // 86400 = seconds in a day

        $diffInSeconds = $arrivalTimeInSeconds - $currentTimeInSeconds;

        return (int) ceil((float) $diffInSeconds / 60);
    }

    /**
     * @param array<string, scalar|null> $arrival
     */
    private function shouldArrivalBeRemoved(array $arrival, int $timeDiffInMinutes): bool
    {
        // If distance is not set and arrival time is in the past beyond the limit, remove it
        if ($arrival['airDistanceInMeters'] === null && $timeDiffInMinutes < -self::PAST_SCHEDULE_TIME_LIMIT_IN_MINUTES) {
            return true;
        }

        if ($timeDiffInMinutes < -self::PAST_SCHEDULE_TIME_LIMIT_IN_MINUTES) {
            // If distance is not set and arrival time is in the past beyond the limit, remove it
            if ($arrival['airDistanceInMeters'] === null) {
                return true;
            }

            // If distance is set and arrival time is in the past beyond the limit, remove it if distance is greater than the limit
            if ($arrival['airDistanceInMeters'] > self::PAST_SCHEDULE_DISTANCE_IN_METERS) {
                return true;
            }
        }

        return false;
    }
}

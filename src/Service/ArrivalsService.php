<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\StopTime;
use App\Helper\GeoDistanceHelper;
use App\Helper\TimeFormatHelper;
use DateTime;
use DateTimeZone;

final class ArrivalsService
{
    private const TIMEZONE = 'Europe/Zagreb';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getArrivalsForStation(string $stopId): array
    {
        // TO-DO: get tripId prefix (e.g. '0_33_') from calendar_dates.txt
        // dummy data works for stopId 1619_21
        // calendar_dates.txt needs to be updated with cron
        // get stop times from stop_times.txt that begin with the tripId prefix
        $calendarPrefix = '0_33_'; // Example prefix, replace with actual logic to fetch from calendar_dates.txt

        // TO-DO: save stop locations to db and query it by $stopId
        $latitude = 45.817608;
        $longitude = 15.875368;

        // Stop times scheduled within a ±1 hour window from current time
        $relevantStopTimes = self::getStopTimesForStation($stopId);
        // Filter stop times by calendar prefix (e.g. schedule is more limited during weekends)
        $relevantStopTimes = array_filter(
            $relevantStopTimes,
            static fn (array $stopTime): bool => str_starts_with($stopTime['trip_id'], $calendarPrefix)
        );

        // Prepare arrivals by defaulting isRealtimeConfirmed to false
        $arrivals = [];
        foreach ($relevantStopTimes as $stopTime) {
            $tripId = $stopTime['trip_id'];
            $arrivals[$tripId] = [
                'routeId' => self::getRouteFromTripId($stopTime['trip_id']),
                'tripId' => $stopTime['trip_id'],
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => $stopTime['arrival_time'],
                'delayInSeconds' => null,
                'calculatedArrivalTime' => $stopTime['arrival_time'],
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ];
        }

        // Get latest GTFS data from cache
        $entityData = (new CachedDataService())->getMinimizedEntityDataFromCache();
        // Filter GTFS data by trip IDs shown in the stop times (ignore unscheduled trips/vehicles)
        $stopTripIds = array_unique(array_map(
            static fn (array $stopTime): string => $stopTime['trip_id'],
            $relevantStopTimes
        ));
        $filteredGtfsData = array_filter($entityData, static fn (array $data): bool => \in_array($data['trip_id'], $stopTripIds, true));

        // Update prepared arrivals with realtime data
        foreach ($filteredGtfsData as $data) {
            $tripId = $data['trip_id'];
            // Schedule confirmed by realtime data
            $arrivals[$tripId]['isRealtimeConfirmed'] = true;
            $arrivals[$tripId]['realtimeDataTimestamp'] = $data['timestamp'];

            if ($data['type'] === 'vehicle') {
                $arrivals[$tripId]['airDistanceInMeters'] = GeoDistanceHelper::getDistanceBetweenPoints(
                    $latitude,
                    $longitude,
                    (float) $data['position']['latitude'],
                    (float) $data['position']['longitude']
                );
            }

            if ($data['type'] === 'tripUpdate') {
                foreach ($data['stopTimeUpdates'] as $stopTimeUpdate) {
                    if ($stopTimeUpdate['stopId'] !== $stopId) {
                        continue;
                    }

                    if ($arrivals[$tripId]['delayInSeconds'] === null) {
                        $arrivals[$tripId]['delayInSeconds'] = 0;
                    }
                    $arrivals[$tripId]['delayInSeconds'] += $stopTimeUpdate['arrivalDelay'];

                    // Calculate the new arrival time based on delay
                    $scheduledArrivalTime = TimeFormatHelper::getSecondsFromTimeString($arrivals[$tripId]['scheduledArrivalTime']);
                    $calculatedArrivalTime = $scheduledArrivalTime + $arrivals[$tripId]['delayInSeconds'];
                    $arrivals[$tripId]['calculatedArrivalTime'] = TimeFormatHelper::getTimeStringFromSeconds($calculatedArrivalTime);
                }
            }
        }

        return array_values($arrivals);
    }

    /**
     * Fetch stop times scheduled within a ±1 hour window from current time
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getStopTimesForStation(string $stopId): array
    {
        /** @var array<int, array<string, scalar>> $stopTimes */
        $stopTimes = (new StopTime())->getArrayBy('stop_id', $stopId);

        $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));
        $currentTimeInSeconds = ($now->format('H') * 3600) + ($now->format('i') * 60) + $now->format('s');

        // tmp
        $currentTimeInSeconds = (14 * 3600) + (33 * 60) + 0; // 14:33:00

        $relevantStopTimes = [];
        foreach ($stopTimes as $stopTime) {
            if (self::isArrivalTimeWithinOneHour((string) $stopTime['arrival_time'], $currentTimeInSeconds)) {
                $relevantStopTimes[] = $stopTime;
            }
        }

        return $relevantStopTimes;
    }

    private static function isArrivalTimeWithinOneHour(string $arrivalTime, int $currentTimeInSeconds): bool
    {
        $arrivalSeconds = TimeFormatHelper::getSecondsFromTimeString($arrivalTime);
        $diff = abs($currentTimeInSeconds - $arrivalSeconds);

        // Handle wrap-around at midnight (e.g., 23:30 vs. 00:10)
        if ($diff > 43200) { // 12 hours in seconds
            $diff = 86400 - $diff; // 86400 = seconds in a day
        }

        return $diff <= 3600; // 3600 seconds = 1 hour
    }

    private static function getRouteFromTripId(string $tripId): ?string
    {
        // Extract route ID from trip ID, assuming the format is consistent
        // Example trip ID: '0_33_12102_121_30083'
        $parts = explode('_', $tripId);

        return $parts[3] ?? null; // Assuming the route ID is always at index 3
    }
}

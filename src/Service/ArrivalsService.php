<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\StopTime;
use App\Exception\BadRequestException;
use App\Exception\InternalServerErrorException;
use App\Helper\GeoDistanceHelper;
use App\Helper\TimeFormatHelper;
use App\Repository\Interfaces\StopRepositoryInterface;
use App\Repository\Interfaces\StopTimeRepositoryInterface;
use App\Service\Interfaces\ArrivalsCleanerServiceInterface;
use App\Service\Interfaces\CachedDataServiceInterface;
use App\Service\Interfaces\CalendarPrefixServiceInterface;
use App\System\Logger;
use DateTime;
use DateTimeZone;
use RuntimeException;

final class ArrivalsService
{
    private const TIMEZONE = 'Europe/Zagreb';

    public function __construct(
        private StopTimeRepositoryInterface $stopTimeRepository,
        private CachedDataServiceInterface $cachedDataService,
        private CalendarPrefixServiceInterface $calendarPrefixService,
        private StopRepositoryInterface $stopRepository,
        private ArrivalsCleanerServiceInterface $arrivalsCleanerService,
    ) {
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    public function getArrivalsForStation(string $stopId): array
    {
        $currentDateTime = new DateTime('now', new DateTimeZone(self::TIMEZONE));

        $calendarPrefix = $this->calendarPrefixService->getCalendarPrefixForDate(
            $currentDateTime
        );

        try {
            [$latitude, $longitude] = $this->stopRepository->getCoordinatesForStopId($stopId);
        } catch (RuntimeException) {
            throw new BadRequestException('Stop with ID ' . $stopId . ' does not exist .');
        }

        // Stop times scheduled within a ±1 hour window from current time
        $relevantStopTimes = self::getStopTimesForStation($stopId);
        // Filter stop times by calendar prefix (e.g. schedule is more limited during weekends)
        $relevantStopTimes = array_filter(
            $relevantStopTimes,
            static fn (StopTime $stopTime): bool => str_starts_with($stopTime->trip_id, $calendarPrefix)
        );

        // Prepare arrivals by defaulting isRealtimeConfirmed to false
        $arrivals = [];
        foreach ($relevantStopTimes as $stopTime) {
            $tripId = $stopTime->trip_id;
            $arrivals[$tripId] = [
                'routeId' => self::getRouteFromTripId($stopTime->trip_id),
                'tripId' => $stopTime->trip_id,
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => $stopTime->arrival_time,
                'delayInSeconds' => null,
                'calculatedArrivalTime' => $stopTime->arrival_time,
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ];
        }

        // Get latest GTFS data from cache
        $entityData = $this->cachedDataService->getMinimizedEntityDataFromCache();
        // Filter GTFS data by trip IDs shown in the stop times (ignore unscheduled trips/vehicles)
        $stopTripIds = array_unique(array_map(
            static fn (StopTime $stopTime): string => $stopTime->trip_id,
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
                if (!\array_key_exists('position', $data)) {
                    Logger::critical(
                        'Vehicle data does not contain position information for trip ID ' . $tripId . ', entity data dump: ' . var_export($entityData, true),
                        'arrivals_service'
                    );
                    throw new InternalServerErrorException('Vehicle data does not contain position information for trip ID: ' . $tripId);
                }

                $arrivals[$tripId]['airDistanceInMeters'] = GeoDistanceHelper::getDistanceBetweenPoints(
                    $latitude,
                    $longitude,
                    (float) $data['position']['latitude'],
                    (float) $data['position']['longitude']
                );
            }

            if ($data['type'] === 'tripUpdate') {
                if (!\array_key_exists('stopTimeUpdates', $data)) {
                    Logger::critical(
                        'Trip update data does not contain stop time updates for trip ID ' . $tripId . ', entity data dump: ' . var_export($entityData, true),
                        'arrivals_service'
                    );
                    throw new InternalServerErrorException('Trip update data does not contain stop time updates for trip ID: ' . $tripId);
                }

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

        return $this->arrivalsCleanerService->cleanArrivalsForDateTime(
            array_values($arrivals),
            $currentDateTime
        );
    }

    /**
     * Fetch stop times scheduled within a ±1 hour window from current time
     *
     * @return StopTime[]
     */
    private function getStopTimesForStation(string $stopId): array
    {
        $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));
        $currentTimeInSeconds = ($now->format('H') * 3600) + ($now->format('i') * 60) + $now->format('s');

        $currentTimeInSeconds = (4 * 3600) + (0 * 60) + 0;

        return $this->stopTimeRepository->getStopTimesWithArrivalWithinOneHourForStopId(
            $stopId,
            (int) $currentTimeInSeconds
        );
    }

    private function getRouteFromTripId(string $tripId): ?string
    {
        // Extract route ID from trip ID, assuming the format is consistent
        // Example trip ID: '0_33_12102_121_30083'
        $parts = explode('_', $tripId);

        return $parts[3] ?? null; // Assuming the route ID is always at index 3
    }
}

<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ArrivalsCleanerService;
use App\Tests\Common\UnitTestCase;
use DateTime;

/**
 * @internal
 */
final class ArrivalsCleanerServiceTest extends UnitTestCase
{
    public function testCleanArrivalsForDateTimeReturnsExpectedArrivals(): void
    {
        // Call cleanArrivalsForDateTime for 4 am
        $arrivalsCleanerService = new ArrivalsCleanerService();
        $cleanedArrivals = $arrivalsCleanerService->cleanArrivalsForDateTime(
            $this->getUncleanArrivals(),
            new DateTime('04:00:00'),
        );

        self::assertSame($this->getExpected(), $cleanedArrivals);
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    private function getUncleanArrivals(): array
    {
        return [
            // Past schedule more than 5 min, do not show up
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '03:49:36',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:49:36',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Past schedule more than 5 min, close proximity
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => 50,
                'scheduledArrivalTime' => '03:49:36',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:49:36',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Past schedule within limit, but very far away, do not show up
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => 1000,
                'scheduledArrivalTime' => '03:59:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:59:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Past schedule more than 5 min, delay present
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => 50,
                'scheduledArrivalTime' => '03:50:00',
                'delayInSeconds' => 660,
                'calculatedArrivalTime' => '04:01:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Past schedule, no realtime data
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '03:58:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:58:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Past schedule, realtime data (delay)
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '03:58:00',
                'delayInSeconds' => 60,
                'calculatedArrivalTime' => '03:59:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Ahead of schedule, no realtime data
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '04:06:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Ahead of schedule, no realtime data, rounding up required
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:35',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '04:06:35',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Ahead of schedule, delay present
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:00',
                'delayInSeconds' => 120,
                'calculatedArrivalTime' => '04:08:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Wrap around past schedule, do not show up
            [
                'routeId' => '31',
                'tripId' => '0_7_3102_31_10086',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '27:10:36',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '27:10:36',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Wrap around ahead of schedule, no realtime data
            [
                'routeId' => '31',
                'tripId' => '0_7_3101_31_10088',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '28:03:54',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '28:03:54',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
            // Wrap around ahead of schedule, delay present
            [
                'routeId' => '31',
                'tripId' => '0_7_3101_31_10088',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '28:03:54',
                'delayInSeconds' => 58,
                'calculatedArrivalTime' => '28:04:52',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    private function getExpected(): array
    {
        return [
            // Past schedule more than 5 min, close proximity
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => 50,
                'scheduledArrivalTime' => '03:49:36',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:49:36',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 0,
            ],
            // Past schedule more than 5 min, delay present
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => 50,
                'scheduledArrivalTime' => '03:50:00',
                'delayInSeconds' => 660,
                'calculatedArrivalTime' => '04:01:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 1,
            ],
            // Past schedule, no realtime data
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '03:58:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '03:58:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 0,
            ],
            // Past schedule, realtime data (delay)
            [
                'routeId' => '8',
                'tripId' => '0_7_801_8_10032',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '03:58:00',
                'delayInSeconds' => 60,
                'calculatedArrivalTime' => '03:59:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 0,
            ],
            // Ahead of schedule, no realtime data
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '04:06:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 6,
            ],
            // Ahead of schedule, no realtime data, rounding up required
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:35',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '04:06:35',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 7,
            ],
            // Ahead of schedule, delay present
            [
                'routeId' => '6',
                'tripId' => '0_7_607_6_12948',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '04:06:00',
                'delayInSeconds' => 120,
                'calculatedArrivalTime' => '04:08:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 8,
            ],
            // Wrap around ahead of schedule, no realtime data
            [
                'routeId' => '31',
                'tripId' => '0_7_3101_31_10088',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '28:03:54',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '28:03:54',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 4,
            ],
            // Wrap around ahead of schedule, delay present
            [
                'routeId' => '31',
                'tripId' => '0_7_3101_31_10088',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '28:03:54',
                'delayInSeconds' => 58,
                'calculatedArrivalTime' => '28:04:52',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
                'vehicleId' => null,
                'arrivalTimeInMinutes' => 5,
            ],
        ];
    }
}

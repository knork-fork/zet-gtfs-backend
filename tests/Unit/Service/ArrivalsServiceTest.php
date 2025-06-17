<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\StopTime;
use App\Repository\Interfaces\StopTimeRepositoryInterface;
use App\Service\ArrivalsService;
use App\Service\Interfaces\CachedDataServiceInterface;
use App\Service\Interfaces\CalendarPrefixServiceInterface;
use App\Tests\Common\UnitTestCase;

/**
 * @internal
 */
final class ArrivalsServiceTest extends UnitTestCase
{
    public function testGetArrivalsForStationReturnsArrivals(): void
    {
        $stopTimeRepositoryMock = $this->createMock(StopTimeRepositoryInterface::class);
        $stopTimeRepositoryMock->expects(self::once())
            ->method('getStopTimesWithArrivalWithinOneHourForStopId')
            ->with('1619_21', self::anything())
            ->willReturn($this->getStopTimes())
        ;

        $cachedDataServiceMock = $this->createMock(CachedDataServiceInterface::class);
        $cachedDataServiceMock->expects(self::once())
            ->method('getMinimizedEntityDataFromCache')
            ->willReturn($this->getMinimizedCacheData())
        ;

        $calendarPrefixServiceMock = $this->createMock(CalendarPrefixServiceInterface::class);
        $calendarPrefixServiceMock->expects(self::once())
            ->method('getCalendarPrefixForDate')
            ->with(self::anything())
            ->willReturn('0_5_')
        ;

        $arrivalsService = new ArrivalsService(
            $stopTimeRepositoryMock,
            $cachedDataServiceMock,
            $calendarPrefixServiceMock,
        );

        $arrivals = $arrivalsService->getArrivalsForStation('1619_21');
        self::assertSame($this->getExpectedArrivals(), $arrivals);
    }

    /**
     * @return StopTime[]
     */
    private function getStopTimes(): array
    {
        $data = [
            [
                'id' => 6632600,
                'stop_id' => '1619_21',
                'trip_id' => '0_5_12102_121_30217',
                'arrival_time' => '13:42:00',
                'stop_sequence' => 12,
            ],
            [
                'id' => 6635555,
                'stop_id' => '1619_21',
                'trip_id' => '0_5_12101_121_30229',
                'arrival_time' => '14:07:00',
                'stop_sequence' => 12,
            ],
            [
                'id' => 6638412,
                'stop_id' => '1619_21',
                'trip_id' => '0_5_12102_121_30241',
                'arrival_time' => '14:32:00',
                'stop_sequence' => 12,
            ],
            [
                'id' => 6640911,
                'stop_id' => '1619_21',
                'trip_id' => '0_5_12101_121_30253',
                'arrival_time' => '14:52:00',
                'stop_sequence' => 12,
            ],
            [
                'id' => 6643597,
                'stop_id' => '1619_21',
                'trip_id' => '0_5_12102_121_30265',
                'arrival_time' => '15:17:00',
                'stop_sequence' => 12,
            ],
        ];

        $stopTimes = [];
        foreach ($data as $item) {
            $stopTime = new StopTime();
            $stopTime->hydrate($item);
            $stopTimes[] = $stopTime;
        }

        return $stopTimes;
    }

    /**
     * @return mixed[]
     */
    private function getMinimizedCacheData(): array
    {
        return [
            [
                'type' => 'vehicle',
                'timestamp' => '1748781180',
                'route_id' => '121',
                'trip_id' => '0_5_12102_121_30241',
                'position' => [
                    'latitude' => 45.817467,
                    'longitude' => 15.87521,
                ],
            ],
            [
                'type' => 'tripUpdate',
                'timestamp' => '1748781180',
                'route_id' => '121',
                'trip_id' => '0_5_12102_121_30241',
                'stopTimeUpdates' => [
                    [
                        'stopId' => '1619_21',
                        'stopSequence' => 12,
                        'arrivalDelay' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    private function getExpectedArrivals(): array
    {
        return [
            [
                'routeId' => '121',
                'tripId' => '0_5_12102_121_30217',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '13:42:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '13:42:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ],
            [
                'routeId' => '121',
                'tripId' => '0_5_12101_121_30229',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '14:07:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '14:07:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ],
            [
                'routeId' => '121',
                'tripId' => '0_5_12102_121_30241',
                'airDistanceInMeters' => 19.893280172253455,
                'scheduledArrivalTime' => '14:32:00',
                'delayInSeconds' => 1,
                'calculatedArrivalTime' => '14:32:01',
                'realtimeDataTimestamp' => '1748781180',
                'isRealtimeConfirmed' => true,
            ],
            [
                'routeId' => '121',
                'tripId' => '0_5_12101_121_30253',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '14:52:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '14:52:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ],
            [
                'routeId' => '121',
                'tripId' => '0_5_12102_121_30265',
                'airDistanceInMeters' => null,
                'scheduledArrivalTime' => '15:17:00',
                'delayInSeconds' => null,
                'calculatedArrivalTime' => '15:17:00',
                'realtimeDataTimestamp' => null,
                'isRealtimeConfirmed' => false,
            ],
        ];
    }
}

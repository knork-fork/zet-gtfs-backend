<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Vehicle;
use App\Repository\Interfaces\VehicleRepositoryInterface;
use App\Service\Interfaces\CachedDataServiceInterface;
use App\Service\VehicleDataService;
use App\Tests\Common\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class VehicleDataServiceTest extends UnitTestCase
{
    public function testSaveVehicleDataToDbForEmptyDbSavesVehiclesWithNoRotation(): void
    {
        $vehicleRepositoryMock = $this->getVehicleRepositoryMock([]);
        $vehicleRepositoryMock->expects(self::never())
            ->method('deleteByIds')
        ;

        $expectedVehicle = new Vehicle();
        $expectedVehicle->hydrate($this->getVehicleMockData()[0]);
        $vehicleRepositoryMock->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (Vehicle $v): bool {
                // Rotation should be 0.0 if vehicle is saved for the first time
                return $v->id === 302 && $v->rotation_deg === 0.0;
            }), true)
        ;

        $vehicleDataService = new VehicleDataService(
            $vehicleRepositoryMock,
            $this->getCachedDataMock()
        );

        $vehicleDataService->saveVehicleDataToDb();
    }

    public function testSaveVehicleDataToDbForFilledDbSavesVehiclesWithUpdatedRotation(): void
    {
        $vehicleRepositoryMock = $this->getVehicleRepositoryMock($this->getVehicleMockData());
        $vehicleRepositoryMock->expects(self::once())
            ->method('deleteByIds')
            // Vehicle from DB is expected to be deleted if it is not present in live data
            ->with([123])
        ;

        $expectedVehicle = new Vehicle();
        $expectedVehicle->hydrate($this->getVehicleMockData()[0]);
        $vehicleRepositoryMock->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (Vehicle $v): bool {
                // Rotation should be calculated if vehicle was already saved
                return $v->id === 302 && $v->rotation_deg === 90.0;
            }), false)
        ;

        $vehicleDataService = new VehicleDataService(
            $vehicleRepositoryMock,
            $this->getCachedDataMock()
        );

        $vehicleDataService->saveVehicleDataToDb();
    }

    public function testGetVehicleDataFromDbForEmptyDbReturnsEmptyArray(): void
    {
        $vehicleDataService = new VehicleDataService(
            $this->getVehicleRepositoryMock([]),
            $this->createMock(CachedDataServiceInterface::class)
        );

        $vehicles = $vehicleDataService->getVehicleDataFromDb();
        self::assertCount(0, $vehicles);
    }

    public function testGetVehicleDataFromDbForFilledDbReturnsVehicleArray(): void
    {
        $vehicleDataService = new VehicleDataService(
            $this->getVehicleRepositoryMock($this->getVehicleMockData()),
            $this->createMock(CachedDataServiceInterface::class)
        );

        $vehicles = $vehicleDataService->getVehicleDataFromDb();
        self::assertCount(2, $vehicles);
        foreach ($vehicles as $vehicle) {
            self::assertInstanceOf(Vehicle::class, $vehicle);
        }
    }

    /**
     * @param array<mixed> $return
     */
    private function getVehicleRepositoryMock(array $return): MockObject&VehicleRepositoryInterface
    {
        $vehicleRepositoryMock = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepositoryMock->expects(self::once())
            ->method('getAll')
            ->willReturn($return)
        ;

        return $vehicleRepositoryMock;
    }

    private function getCachedDataMock(): CachedDataServiceInterface
    {
        $cachedDataMock = $this->createMock(CachedDataServiceInterface::class);
        $cachedDataMock->expects(self::once())
            ->method('getMinimizedEntityDataFromCache')
            ->willReturn($this->getMinimizedCacheData())
        ;

        return $cachedDataMock;
    }

    /**
     * @return array<int, array<string, int|float>>
     */
    private function getVehicleMockData(): array
    {
        return [
            [
                'id' => 302,
                'route_id' => 121,
                'position_lat' => 0, // equator latitude
                'position_long' => 14.87521,
                'rotation_deg' => 0,
            ],
            [
                'id' => 123,
                'route_id' => 121,
                'position_lat' => 45.817467,
                'position_long' => 15.87521,
                'rotation_deg' => 0,
            ],
        ];
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
                    'latitude' => 0,
                    'longitude' => 15.87521,
                ],
                'vehicle' => [
                    'id' => '302',
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
}

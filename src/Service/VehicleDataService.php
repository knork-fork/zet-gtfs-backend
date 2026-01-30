<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Vehicle;
use App\Helper\VehicleRotationHelper;
use App\Repository\Interfaces\VehicleRepositoryInterface;
use App\Service\Interfaces\CachedDataServiceInterface;
use App\Service\Interfaces\VehicleDataServiceInterface;

final class VehicleDataService implements VehicleDataServiceInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicleRepository,
        private CachedDataServiceInterface $cachedDataService,
    ) {
    }

    public function saveVehicleDataToDb(): void
    {
        // Get vehicles saved to DB in a previous cache fetch
        $vehicleDataDb = $this->vehicleRepository->getAll();
        $dbById = [];
        foreach ($vehicleDataDb as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $id = (string) $row['id'];
            $dbById[$id] = $row;
        }

        // Get vehicles from current cache fetch
        $vehicleDataCache = $this->cachedDataService->getMinimizedEntityDataFromCache(ignoreReadActivity: true);
        $cacheById = [];
        foreach ($vehicleDataCache as $data) {
            if ($data['type'] !== 'vehicle') {
                continue;
            }

            $id = $data['vehicle']['id'] ?? null;

            $cacheById[$id] = [
                'id' => (string) $id,
                'route_id' => (int) $data['route_id'],
                'position_lat' => isset($data['position']['latitude']) ? (float) $data['position']['latitude'] : 0.0,
                'position_long' => isset($data['position']['longitude']) ? (float) $data['position']['longitude'] : 0.0,
                'rotation_deg' => 0.0, // Initial rotation, it takes at least two position points to calculate rotation
            ];
        }

        $dbIds = array_keys($dbById);
        $cacheIds = array_keys($cacheById);

        $toInsertIds = array_diff($cacheIds, $dbIds);
        $toDeleteIds = array_diff($dbIds, $cacheIds);
        $toUpdateIds = array_intersect($dbIds, $cacheIds);

        // Delete vehicles leftover from previous cache fetch, but not present in current cache fetch
        if (\count($toDeleteIds) > 0) {
            $this->vehicleRepository->deleteByIds(array_values($toDeleteIds));
        }

        // Insert new vehicles from current cache fetch
        foreach ($toInsertIds as $id) {
            $row = $cacheById[$id];
            $vehicle = new Vehicle();
            $vehicle->hydrate([
                'route_id' => $row['route_id'],
                'position_lat' => $row['position_lat'],
                'position_long' => $row['position_long'],
                'rotation_deg' => $row['rotation_deg'],
            ]);
            $vehicle->id = (int) $row['id'];

            $this->vehicleRepository->save($vehicle, true);
        }

        // Update existing vehicles with new data from current cache fetch
        foreach ($toUpdateIds as $id) {
            $row = $cacheById[$id];
            $vehicle = new Vehicle();
            $vehicle->hydrate([
                'route_id' => $row['route_id'],
                'position_lat' => $row['position_lat'],
                'position_long' => $row['position_long'],
                'rotation_deg' => $dbById[$id]['rotation_deg'] ?? 0.0,
            ]);
            $vehicle->id = (int) $row['id'];

            VehicleRotationHelper::calculateRotationForVehicle(
                $vehicle,
                (float) ($dbById[$id]['position_lat'] ?? 0.0),
                (float) ($dbById[$id]['position_long'] ?? 0.0),
            );

            $this->vehicleRepository->save($vehicle, false);
        }
    }

    /**
     * @return Vehicle[]
     */
    public function getVehicleDataFromDb(): array
    {
        $vehicleArr = $this->vehicleRepository->getAll();

        $vehicles = [];
        foreach ($vehicleArr as $vehicleData) {
            $vehicle = new Vehicle();
            $vehicle->hydrate($vehicleData);
            $vehicles[] = $vehicle;
        }

        return $vehicles;
    }
}

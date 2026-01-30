<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vehicle;
use App\Repository\Interfaces\VehicleRepositoryInterface;

/**
 * @extends AbstractRepository<Vehicle>
 */
final class VehicleRepository extends AbstractRepository implements VehicleRepositoryInterface
{
    protected function getEntityClass(): string
    {
        return Vehicle::class;
    }

    protected function getTableName(): string
    {
        return 'vehicles';
    }
}

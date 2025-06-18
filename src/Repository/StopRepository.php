<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Stop;
use App\Repository\Interfaces\StopRepositoryInterface;

/**
 * @extends AbstractRepository<Stop>
 */
final class StopRepository extends AbstractRepository implements StopRepositoryInterface
{
    protected function getEntityClass(): string
    {
        return Stop::class;
    }

    protected function getTableName(): string
    {
        return 'stops';
    }

    public function getCoordinatesForStopId(string $stopId): array
    {
        $stop = $this->getBy('stop_id', $stopId);

        return [$stop->stop_lat, $stop->stop_lon];
    }
}

<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use App\Entity\Stop;
use PDOException;
use RuntimeException;

/**
 * @extends AbstractRepositoryInterface<Stop>
 */
interface StopRepositoryInterface extends AbstractRepositoryInterface
{
    /**
     * @return array{0: float, 1: float}
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getCoordinatesForStopId(string $stopId): array;
}

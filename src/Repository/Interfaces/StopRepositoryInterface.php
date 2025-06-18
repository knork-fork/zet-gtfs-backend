<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use PDOException;
use RuntimeException;

interface StopRepositoryInterface
{
    /**
     * @return array{0: float, 1: float}
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getCoordinatesForStopId(string $stopId): array;
}

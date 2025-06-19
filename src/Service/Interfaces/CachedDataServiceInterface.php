<?php
declare(strict_types=1);

namespace App\Service\Interfaces;

use App\Exception\BadRequestException;

interface CachedDataServiceInterface
{
    /**
     * @return mixed[]
     */
    public function getFullDataFromCache(): array;

    /**
     * @return array<int, array{
     *     type: 'vehicle'|'tripUpdate',
     *     timestamp: string|int,
     *     route_id: string,
     *     trip_id: string,
     *     stopTimeUpdates?: array<int, array{
     *         stopId: string,
     *         stopSequence: int,
     *         arrivalDelay: int
     *     }>,
     *     position?: array{
     *         latitude: float,
     *         longitude: float
     *     },
     *     vehicle?: array{
     *         id: string
     *     }
     * }>
     *
     * @throws BadRequestException if no GTFS data is available
     */
    public function getMinimizedEntityDataFromCache(): array;
}

<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BadRequestException;
use App\Service\Interfaces\CachedDataServiceInterface;

final class CachedDataService implements CachedDataServiceInterface
{
    public const LAST_CACHE_READ_FILENAME = '/application/var/cache/last_cache_read';

    public GtfsDataService $gtfsDataService;

    public function __construct()
    {
        // Register cache read every time the service is instantiated
        // This helps us stop polling the GTFS data if the cache is not read for a while
        touch(self::LAST_CACHE_READ_FILENAME);

        $this->gtfsDataService = new GtfsDataService();
    }

    public function getFullDataFromCache(): array
    {
        $this->gtfsDataService->fetchDataToCacheIfOutdated();
        $json = file_get_contents(GtfsDataService::GTFS_CACHE_FILENAME);

        if (!\is_string($json)) {
            throw new BadRequestException('Error reading GTFS cache');
        }
        $jsonObject = json_decode($json, true);
        if (json_last_error() !== \JSON_ERROR_NONE || !\is_array($jsonObject)) {
            throw new BadRequestException(\sprintf('Error while decoding JSON: %s', json_last_error_msg()));
        }

        return $jsonObject;
    }

    public function getMinimizedEntityDataFromCache(): array
    {
        // TO-DO: implement caching of this data to prevent reformatting same data every time

        $cacheData = $this->getFullDataFromCache();
        $entities = $cacheData['entity'] ?? [];
        if (!\is_array($entities) || empty($entities)) {
            throw new BadRequestException('No GTFS data available');
        }

        $formattedData = [];
        foreach ($entities as $entity) {
            if (!\is_array($entity) || !\array_key_exists('vehicle', $entity) && !\array_key_exists('tripUpdate', $entity)) {
                continue; // Skip invalid entities
            }
            $type = \array_key_exists('vehicle', $entity) ? 'vehicle' : 'tripUpdate';
            $data = [
                'type' => $type,
                'timestamp' => $entity[$type]['timestamp'] ?? '',
                'route_id' => $entity[$type]['trip']['routeId'] ?? '',
                'trip_id' => $entity[$type]['trip']['tripId'] ?? '',
            ];

            if ($type === 'tripUpdate') {
                $data['stopTimeUpdates'] = [];
                foreach ($entity['tripUpdate']['stopTimeUpdate'] as $stopTimeUpdate) {
                    $data['stopTimeUpdates'][] = [
                        'stopId' => $stopTimeUpdate['stopId'] ?? '',
                        'stopSequence' => $stopTimeUpdate['stopSequence'] ?? 0,
                        'arrivalDelay' => $stopTimeUpdate['arrival']['delay'] ?? 0,
                    ];
                }
            } else {
                $data['position'] = [
                    'latitude' => $entity['vehicle']['position']['latitude'] ?? 0.0,
                    'longitude' => $entity['vehicle']['position']['longitude'] ?? 0.0,
                ];
                $data['vehicle'] = [
                    'id' => $entity['vehicle']['vehicle']['id'] ?? '',
                ];
            }

            $formattedData[] = $data;
        }

        return $formattedData;
    }
}

<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BadRequestException;

final class CachedDataService
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

    /**
     * @return mixed[]
     */
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
}

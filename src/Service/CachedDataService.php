<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BadRequestException;

final class CachedDataService
{
    public const GTFS_CACHE_FILENAME = '/application/var/cache/latest_gtfs.json';
    public const LAST_CACHE_READ_FILENAME = '/application/var/cache/last_cache_read';
    public const FRONTEND_COMMIT_FILENAME = '/application/var/cache/frontend_commit.txt';
    public const BACKEND_COMMIT_FILENAME = '/application/var/cache/backend_commit.txt';

    public function __construct()
    {
        // Register cache read every time the service is instantiated
        // This helps us stop polling the GTFS data if the cache is not read for a while
        touch(self::LAST_CACHE_READ_FILENAME);
    }

    /**
     * @return mixed[]|null
     */
    public function getFullDataFromCache(): ?array
    {
        if (!file_exists(self::GTFS_CACHE_FILENAME)) {
            return null;
        }

        $json = file_get_contents(self::GTFS_CACHE_FILENAME);

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

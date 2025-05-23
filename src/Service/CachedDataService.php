<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BadRequestException;

final class CachedDataService
{
    public const GTFS_CACHE_FILENAME = '/application/var/cache/latest_gtfs.json';

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

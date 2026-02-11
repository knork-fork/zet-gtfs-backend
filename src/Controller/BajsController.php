<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;
use App\Service\BajsDataService;

final class BajsController
{
    public static function getBikeStations(): JsonResponse
    {
        $dynamicCache = BajsDataService::BAJS_CACHE_FILENAME;
        if (file_exists($dynamicCache)) {
            $filePath = $dynamicCache;
        } else {
            $filePath = '/application/scripts/gtfs/static_gtfs_files/bajs.json';
        }

        /** @var mixed[] $data */
        $data = json_decode((string) file_get_contents($filePath), true);

        return new JsonResponse($data);
    }
}

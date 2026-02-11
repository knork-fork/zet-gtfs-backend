<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\JsonResponse;

final class BajsController
{
    public static function getBikeStations(): JsonResponse
    {
        // to-do: fetch from var cache if exists, otherwise fetch from static_gtfs_files/bajs.txt as a fallback
        $filePath = __DIR__ . '/../../scripts/gtfs/static_gtfs_files/bajs.txt';
        /** @var mixed[] $data */
        $data = json_decode((string) file_get_contents($filePath), true);

        return new JsonResponse($data);
    }
}

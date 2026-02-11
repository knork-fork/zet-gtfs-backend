<?php
declare(strict_types=1);

namespace App\Service;

use App\System\Logger;
use Exception;

final class BajsDataService
{
    public const BAJS_CACHE_FILENAME = '/application/var/cache/bajs_dynamic.json';

    private const NEXTBIKE_URL = 'https://maps.nextbike.net/maps/nextbike-live.json?domains=hd&list_cities=0&bikes=0';

    /**
     * @throws Exception
     */
    public function fetchDataToCache(): void
    {
        $timeNow = microtime(true);

        $json = file_get_contents(self::NEXTBIKE_URL);
        if ($json === false) {
            Logger::error('Failed to fetch data from nextbike API', 'bajs_cron');
            throw new Exception('Failed to fetch data from nextbike API');
        }

        $data = json_decode($json, true);
        if (!\is_array($data)) {
            Logger::error('Invalid JSON response from nextbike API: ' . substr($json, 0, 400), 'bajs_cron');
            throw new Exception('Invalid JSON response from nextbike API');
        }

        /** @var mixed[] $countries */
        $countries = $data['countries'] ?? [];
        /** @var mixed[] $firstCountry */
        $firstCountry = $countries[0] ?? [];
        /** @var mixed[] $cities */
        $cities = $firstCountry['cities'] ?? [];
        /** @var mixed[] $firstCity */
        $firstCity = $cities[0] ?? [];
        /** @var mixed[] $places */
        $places = $firstCity['places'] ?? [];

        if (empty($places)) {
            Logger::error('Unexpected nextbike API response structure: ' . var_export($data, true), 'bajs_cron');
            throw new Exception('Unexpected nextbike API response structure');
        }

        $stations = [];
        foreach ($places as $place) {
            if (!\is_array($place)) {
                continue;
            }
            $stations[] = [
                'uid' => $place['uid'],
                'lat' => $place['lat'],
                'lng' => $place['lng'],
                'name' => $place['name'],
                'bikes_available_to_rent' => $place['bikes_available_to_rent'],
                'bike_racks' => $place['bike_racks'],
            ];
        }

        $encodedJson = json_encode($stations, \JSON_THROW_ON_ERROR);
        file_put_contents(self::BAJS_CACHE_FILENAME, $encodedJson);

        $fetchDuration = microtime(true) - $timeNow;
        Logger::info(\sprintf(
            'Bajs data fetched and cached successfully in %.2f seconds (%d stations)',
            $fetchDuration,
            \count($stations),
        ), 'bajs_cron');
    }
}

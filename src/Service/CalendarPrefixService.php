<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Interfaces\CalendarPrefixServiceInterface;
use App\System\Logger;
use DateTime;
use RuntimeException;

final class CalendarPrefixService implements CalendarPrefixServiceInterface
{
    private const CALENDAR_DATES_FILENAME = '/application/scripts/gtfs/static_gtfs_files/calendar_dates.txt';

    public function getCalendarPrefixForDate(DateTime $date): string
    {
        $targetDate = $date->format('Ymd');

        $handle = fopen(self::CALENDAR_DATES_FILENAME, 'r');
        if (!$handle) {
            throw new RuntimeException('Failed to open calendar_dates.txt');
        }

        // Skip header
        fgetcsv($handle, 0, ',', '"', '\\');

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            [$serviceId, $csvDate] = $row;

            if ($csvDate === $targetDate) {
                return trim((string) $serviceId, '"') . '_'; // append additional underscore to match the format of trip_id
            }
        }

        fclose($handle);

        // This is API breaking behavior, arrivals can't be fetched if dates are not found in calendar_dates.txt
        // This usually means that scripts/gtfs/update_schedule.sh needs to be run to update the static GTFS data
        Logger::emergency('No matching calendar prefix found for date: ' . $targetDate, 'calendar_prefix_service');
        throw new RuntimeException("No matching calendar prefix for date: {$targetDate}");
    }
}

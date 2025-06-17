<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Interfaces\CalendarPrefixServiceInterface;
use DateTime;

final class CalendarPrefixService implements CalendarPrefixServiceInterface
{
    public function getCalendarPrefixForDate(DateTime $date): string
    {
        // Example prefix, replace with actual logic to fetch from calendar_dates.txt
        return '0_5_';
    }
}

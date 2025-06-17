<?php
declare(strict_types=1);

namespace App\Service\Interfaces;

use DateTime;

interface CalendarPrefixServiceInterface
{
    public function getCalendarPrefixForDate(DateTime $date): string;
}

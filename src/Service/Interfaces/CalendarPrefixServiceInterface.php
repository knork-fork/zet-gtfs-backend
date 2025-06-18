<?php
declare(strict_types=1);

namespace App\Service\Interfaces;

use DateTime;
use RuntimeException;

interface CalendarPrefixServiceInterface
{
    /**
     * @throws RuntimeException
     */
    public function getCalendarPrefixForDate(DateTime $date): string;
}

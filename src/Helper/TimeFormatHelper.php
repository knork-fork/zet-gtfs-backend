<?php
declare(strict_types=1);

namespace App\Helper;

use DateTimeImmutable;
use DateTimeZone;

final class TimeFormatHelper
{
    public static function getSecondsFromTimeString(string $time): int
    {
        [$h, $m, $s] = explode(':', $time);

        return ((int) $h * 3600) + ((int) $m * 60) + (int) $s;
    }

    public static function getTimeStringFromSeconds(int $seconds, bool $accountForTimezone = false): string
    {
        // Handle wrap-around at midnight
        if ($seconds < 0) {
            $seconds = 24 * 3600 + $seconds;
        }
        if ($seconds >= 24 * 3600) {
            $seconds %= (24 * 3600);
        }

        $h = (int) ($seconds / 3600);
        $m = (int) (($seconds % 3600) / 60);
        $s = $seconds % 60;

        if ($accountForTimezone) {
            // Get offset in hours for Europe/Zagreb timezone
            $tz = new DateTimeZone('Europe/Zagreb');
            $now = new DateTimeImmutable('now', $tz);
            $offset = $tz->getOffset($now) / 3600;

            $h += $offset;

            if ($h >= 24) {
                $h -= 24;
            } elseif ($h < 0) {
                $h += 24;
            }
        }

        return \sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}

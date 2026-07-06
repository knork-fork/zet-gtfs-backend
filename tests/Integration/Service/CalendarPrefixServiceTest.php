<?php
declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Service\CalendarPrefixService;
use App\Tests\Common\IntegrationTestCase;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class CalendarPrefixServiceTest extends IntegrationTestCase
{
    private const TIMEZONE = 'Europe/Zagreb';

    #[DataProvider('getDates')]
    public function testGetCalendarPrefixForDate(string $input, string $expected): void
    {
        $calendarPrefixService = new CalendarPrefixService();

        $date = new DateTime($input, new DateTimeZone(self::TIMEZONE));
        $prefix = $calendarPrefixService->getCalendarPrefixForDate($date);

        self::assertSame($expected, $prefix);
    }

    /**
     * @return array<mixed>
     */
    public static function getDates(): array
    {
        // These values depend on calendar_dates.txt and need to be updated if scripts/gtfs/update_schedule.sh is run
        return [
            'Monday' => ['input' => '2026-07-06', 'expected' => '0_1_'],
            'Tuesday' => ['input' => '2026-07-07', 'expected' => '0_1_'],
            'Wednesday' => ['input' => '2026-07-08', 'expected' => '0_1_'],
            'Thursday' => ['input' => '2026-07-09', 'expected' => '0_1_'],
            'Friday' => ['input' => '2026-07-10', 'expected' => '0_1_'],
            'Saturday' => ['input' => '2026-07-11', 'expected' => '0_2_'],
            'Sunday' => ['input' => '2026-07-12', 'expected' => '0_3_'],
        ];
    }
}

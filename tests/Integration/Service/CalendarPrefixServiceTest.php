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
            'Monday' => ['input' => '2025-06-09', 'expected' => '0_3_'],
            'Tuesday' => ['input' => '2025-06-10', 'expected' => '0_3_'],
            'Wednesday' => ['input' => '2025-06-11', 'expected' => '0_3_'],
            'Thursday' => ['input' => '2025-06-12', 'expected' => '0_3_'],
            'Friday' => ['input' => '2025-06-13', 'expected' => '0_3_'],
            'Saturday' => ['input' => '2025-06-14', 'expected' => '0_4_'],
            'Sunday' => ['input' => '2025-06-15', 'expected' => '0_5_'],
        ];
    }
}

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
            'Monday' => ['input' => '2025-06-23', 'expected' => '0_20_'],
            'Tuesday' => ['input' => '2025-06-24', 'expected' => '0_20_'],
            'Wednesday' => ['input' => '2025-06-25', 'expected' => '0_20_'],
            'Thursday' => ['input' => '2025-06-26', 'expected' => '0_20_'],
            'Friday' => ['input' => '2025-06-27', 'expected' => '0_20_'],
            'Saturday' => ['input' => '2025-06-28', 'expected' => '0_23_'],
            'Sunday' => ['input' => '2025-06-29', 'expected' => '0_24_'],
        ];
    }
}

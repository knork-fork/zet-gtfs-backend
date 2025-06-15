<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;

/**
 * @internal
 */
final class StopControllerTest extends FunctionalTestCase
{
    public function testGetRouteGeographyReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/arrivals/1619_21'
        );

        $json = $this->decodeJsonFromResponse($response);

        $arrival = $json[0] ?? null;
        self::assertIsArray($arrival);
        self::assertArrayHasKey('routeId', $arrival);
        self::assertArrayHasKey('tripId', $arrival);
        self::assertArrayHasKey('airDistanceInMeters', $arrival);
        self::assertArrayHasKey('scheduledArrivalTime', $arrival);
        self::assertArrayHasKey('delayInSeconds', $arrival);
        self::assertArrayHasKey('calculatedArrivalTime', $arrival);
        self::assertArrayHasKey('realtimeDataTimestamp', $arrival);
        self::assertArrayHasKey('isRealtimeConfirmed', $arrival);
    }
}

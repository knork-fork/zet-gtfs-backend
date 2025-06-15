<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;
use App\Tests\Common\Response;

/**
 * @internal
 */
final class StatusControllerTest extends FunctionalTestCase
{
    public function testStatusEndpointReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/status'
        );

        $json = $this->decodeJsonFromResponse($response);
        self::assertArrayHasKey('status', $json);
        self::assertSame('ok', $json['status']);
    }

    public function testInvalidMethodReturns404(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/api/status'
        );

        $json = $this->decodeJsonFromResponse($response, Response::HTTP_NOT_FOUND);
        self::assertArrayHasKey('error', $json);
        self::assertSame('Path not found', $json['error']);
    }

    public function testInvalidRouteReturns404(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/no-route'
        );

        $json = $this->decodeJsonFromResponse($response, Response::HTTP_NOT_FOUND);
        self::assertArrayHasKey('error', $json);
        self::assertSame('Path not found', $json['error']);
    }

    public function testInfoEndpointReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/info'
        );

        $json = $this->decodeJsonFromResponse($response);
        self::assertArrayHasKey('description', $json);
        self::assertSame('GTFS-RT to JSON converter', $json['description']);
        self::assertArrayHasKey('zet_url', $json);
        self::assertSame('https://test.com', $json['zet_url']);
        self::assertArrayHasKey('polling_interval_in_seconds', $json);
        self::assertArrayHasKey('stop_polling_after_inactivity_in_seconds', $json);
        self::assertArrayHasKey('last_cache_read', $json);
        self::assertArrayHasKey('last_cache_write', $json);
        self::assertArrayHasKey('is_currently_polling', $json);
        self::assertArrayHasKey('frontend_version', $json);
        self::assertArrayHasKey('backend_version', $json);
        self::assertArrayHasKey('should_update', $json);
    }
}

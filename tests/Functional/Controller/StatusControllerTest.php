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
}

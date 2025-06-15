<?php
declare(strict_types=1);

namespace App\Tests\Common;

use Error;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class FunctionalTestCase extends TestCase
{
    public const BASE_URL = 'http://zet-gtfs-webserver';

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $headers
     */
    protected function makeRequest(string $method, string $uri, array $params = [], array $headers = [], string $baseUrl = self::BASE_URL): Response
    {
        if (!empty(getenv('XDEBUG_SESSION_START'))) {
            $uri .= !str_contains($uri, '?') ? '?' : '&';
            $uri .= 'XDEBUG_SESSION_START=' . getenv('XDEBUG_SESSION_START');
        }

        if ($method === Request::METHOD_GET && $params) {
            $uri .= !str_contains($uri, '?') ? '?' : '&';
            $uri .= http_build_query($params);
            $params = [];
        }

        // Set env override header
        $headers[] = 'X-App-Env: test';

        if (!Request::isHeaderSet('Content-Type', $headers)) {
            $headers[] = 'Content-Type: application/json';
        }
        if (!Request::isHeaderSet('Accept', $headers)) {
            $headers[] = 'Accept: application/json';
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }
        curl_setopt_array($ch, [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_URL => $baseUrl . $uri,
            \CURLOPT_SSL_VERIFYHOST => 0,
            \CURLOPT_SSL_VERIFYPEER => 0,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_POSTFIELDS => json_encode($params),
            \CURLOPT_HTTPHEADER => $headers,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            $errorMessage = curl_error($ch);
            if ($errorMessage === 'Unsupported HTTP version in response') {
                // This error message is not helpful and is returned by gc-app-test's internal PHP server, swap it with something barely more helpful
                throw new Error('Incomplete HTTP response, error 500 thrown by gc-app-test\'s internal PHP server...');
            }
            throw new RuntimeException(curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $responseHeaders = [];

        curl_close($ch);

        return new Response(
            (string) $result,
            $statusCode,
            $responseHeaders
        );
    }

    /**
     * @return mixed[]
     */
    protected function decodeJsonFromResponse(Response $response, ?int $expectedStatusCode = Response::HTTP_OK): array
    {
        self::assertSame($expectedStatusCode, $response->getStatusCode(), 'Response status code invalid.');
        self::assertJson($response->getContent());

        $responseArray = json_decode($response->getContent(), true);
        self::assertIsArray($responseArray);

        return $responseArray;
    }
}

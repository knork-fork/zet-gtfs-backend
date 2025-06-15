<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;
use App\Tests\Common\Response;

/**
 * @internal
 */
final class RouteGeographyControllerTest extends FunctionalTestCase
{
    public function testGetRouteGeographyReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/route/6/geography'
        );

        $json = $this->decodeJsonFromResponse($response);
        self::assertArrayHasKey('type', $json);
        self::assertSame('FeatureCollection', $json['type']);
        self::assertArrayHasKey('features', $json);
        
        $features = $json['features'];
        self::assertIsArray($features);

        $feature = $features[0];
        self::assertIsArray($feature);
        self::assertArrayHasKey('type', $feature);
        self::assertSame('Feature', $feature['type']);
        self::assertArrayHasKey('properties', $feature);
        self::assertArrayHasKey('geometry', $feature);

        $geometry = $feature['geometry'];
        self::assertIsArray($geometry);
        self::assertArrayHasKey('type', $geometry);
        self::assertSame('LineString', $geometry['type']);
        self::assertArrayHasKey('coordinates', $geometry);
        
        $coordinates = $geometry['coordinates'];
        self::assertIsArray($coordinates);

        $coordinate = $coordinates[0] ?? null;
        self::assertIsArray($coordinate);
        self::assertCount(2, $coordinate);
        self::assertIsFloat($coordinate[0]);
        self::assertIsFloat($coordinate[1]);
    }
}

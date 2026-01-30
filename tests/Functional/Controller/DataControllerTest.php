<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;

/**
 * @internal
 */
final class DataControllerTest extends FunctionalTestCase
{
    public function testGetAllDataReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/get_data'
        );

        $json = $this->decodeJsonFromResponse($response);

        self::assertArrayHasKey('header', $json);
        $header = $json['header'];
        self::assertIsArray($header);
        self::assertArrayHasKey('gtfsRealtimeVersion', $header);
        self::assertArrayHasKey('incrementality', $header);
        self::assertArrayHasKey('timestamp', $header);

        self::assertArrayHasKey('entity', $json);
        $entities = $json['entity'];
        self::assertIsArray($entities);

        // Vehicle entity
        $entity = $entities[0] ?? null;
        self::assertIsArray($entity);
        self::assertArrayHasKey('id', $entity);
        self::assertArrayHasKey('vehicle', $entity);

        // TripUpdate entity
        $entity = $entities[1] ?? null;
        self::assertIsArray($entity);
        self::assertArrayHasKey('id', $entity);
        self::assertArrayHasKey('tripUpdate', $entity);
    }

    public function testGetVehicleDataReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/get_vehicle_data'
        );

        $json = $this->decodeJsonFromResponse($response);

        $vehicle = $json[0] ?? null;
        self::assertIsArray($vehicle);
        // Values based on gtfs_dummy.json content
        self::assertSame(302, $vehicle['id']);
        self::assertSame(121, $vehicle['route_id']);
        self::assertSame(45.817467, $vehicle['position_lat']);
        self::assertSame(15.87521, $vehicle['position_long']);
        self::assertIsFloat($vehicle['rotation_deg']);
    }
}

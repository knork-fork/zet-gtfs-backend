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
}

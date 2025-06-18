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
    public function testGetArrivalsReturnsResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/api/arrivals/1619_21'
        );

        // We can only check for validity of the response, not the content,
        // because the data is dynamic and changes frequently.
        $json = $this->decodeJsonFromResponse($response);
    }
}

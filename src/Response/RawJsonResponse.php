<?php
declare(strict_types=1);

namespace App\Response;

final class RawJsonResponse extends Response
{
    public function __construct(
        string $data,
        int $statusCode = Response::HTTP_OK
    ) {
        parent::__construct($data, $statusCode);
    }
}

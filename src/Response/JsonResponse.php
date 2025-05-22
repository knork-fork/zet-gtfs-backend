<?php
declare(strict_types=1);

namespace App\Response;

final class JsonResponse extends Response
{
    /**
     * @param mixed[] $data
     */
    public function __construct(
        array $data,
        int $statusCode = Response::HTTP_OK
    ) {
        parent::__construct($data, $statusCode);
    }
}

<?php
declare(strict_types=1);

namespace App\Response;

abstract class Response
{
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;

    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * @param mixed[] $data
     */
    public function __construct(
        private array $data,
        private int $statusCode
    ) {
    }

    public function output(?int $statusCode = null): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode ?? $this->statusCode);
        echo json_encode($this->data);
    }
}

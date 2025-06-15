<?php
declare(strict_types=1);

namespace App\Tests\Common;

final class Request
{
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PURGE = 'PURGE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE = 'TRACE';
    public const METHOD_CONNECT = 'CONNECT';

    /**
     * @param mixed[] $headers
     */
    public static function isHeaderSet(string $targetHeader, array $headers): bool
    {
        foreach ($headers as $header) {
            if (\is_string($header) && str_starts_with($header, $targetHeader)) {
                return true;
            }
        }

        return false;
    }
}

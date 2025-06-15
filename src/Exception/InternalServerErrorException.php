<?php
declare(strict_types=1);

namespace App\Exception;

use App\Response\Response;
use Exception;
use Throwable;

final class InternalServerErrorException extends Exception
{
    public function __construct(
        string $message = 'Internal Server Error',
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

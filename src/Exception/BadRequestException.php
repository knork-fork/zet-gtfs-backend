<?php
declare(strict_types=1);

namespace App\Exception;

use App\Response\Response;
use Exception;
use Throwable;

final class BadRequestException extends Exception
{
    public function __construct(
        string $message = 'Bad Request',
        int $code = Response::HTTP_BAD_REQUEST,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

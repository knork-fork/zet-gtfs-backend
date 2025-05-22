<?php
declare(strict_types=1);

namespace App\Response;

use Throwable;

final class ExceptionResponse extends Response
{
    public function __construct(
        private Throwable $exception,
        public readonly bool $suppressThrow = false,
    ) {
        parent::__construct(
            ['error' => $exception->getMessage()],
            (int) $this->exception->getCode()
        );
    }
}

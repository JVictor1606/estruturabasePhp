<?php

declare(strict_types=1);

namespace Modular\Shared\Exceptions;

use Modular\Shared\Enums\HttpStatus;

abstract class DomainException extends \Exception
{
    protected int $statusCode = HttpStatus::BAD_REQUEST->value;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

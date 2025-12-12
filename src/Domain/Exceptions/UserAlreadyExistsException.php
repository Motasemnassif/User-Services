<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

use Exception;

final class UserAlreadyExistsException extends Exception
{
    public function __construct(string $message = 'User already exists', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}

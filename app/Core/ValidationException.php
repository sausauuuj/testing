<?php
declare(strict_types=1);

namespace App\Core;

use Exception;

final class ValidationException extends Exception
{
    public function __construct(
        string $message = 'The submitted data is invalid.',
        private readonly array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

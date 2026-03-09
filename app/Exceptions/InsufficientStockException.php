<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        private readonly array $stockErrors,
        string $message = 'One or more products do not have sufficient stock.'
    ) {
        parent::__construct($message);
    }

    public function getStockErrors(): array
    {
        return $this->stockErrors;
    }
}
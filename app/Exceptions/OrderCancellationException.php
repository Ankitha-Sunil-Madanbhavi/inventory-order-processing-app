<?php

namespace App\Exceptions;

use RuntimeException;

class OrderCancellationException extends RuntimeException
{
    public function __construct(string $status)
    {
        parent::__construct(
            "Orders with status '{$status}' cannot be cancelled."
        );
    }
}
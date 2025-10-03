<?php

namespace TJDFT\Laravel\Exceptions;

use Exception;
use Mary\Exceptions\ToastException;

/**
 * Será convertida em um Toast do maryUI
 */
class AppException extends Exception
{
    public function __construct(string $message = "", public ?string $description = null)
    {
        parent::__construct($message);
    }

    public function report(): void
    {
        throw ToastException::error($this->message, $this->description);
    }
}

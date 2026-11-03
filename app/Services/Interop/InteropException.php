<?php

namespace App\Services\Interop;

use RuntimeException;

/** A refusal meant for the person at the screen or the caller of the API. `status` is the HTTP answer. */
class InteropException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422, public readonly string $code_ = 'invalid')
    {
        parent::__construct($message);
    }
}

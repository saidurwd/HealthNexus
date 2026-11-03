<?php

namespace App\Services\Portal;

use RuntimeException;

/** A refusal the patient may be told about. `status` is the HTTP answer: 422 wrong input, 429 too many tries, 403 not allowed, 409 state conflict. */
class PortalException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422, public readonly array $extra = [])
    {
        parent::__construct($message);
    }
}

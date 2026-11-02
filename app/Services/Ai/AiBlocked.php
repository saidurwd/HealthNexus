<?php

namespace App\Services\Ai;

use RuntimeException;

/** A request the gateway refused. The message is safe to show the person; the reason is also on the request record. */
class AiBlocked extends RuntimeException {}

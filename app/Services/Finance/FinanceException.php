<?php

namespace App\Services\Finance;

use RuntimeException;

/** A rule of the books was broken; the message is written for the accountant. */
class FinanceException extends RuntimeException {}

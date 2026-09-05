<?php

namespace Modules\Payment\Exceptions;

use RuntimeException;

final class PaymentConcurrencyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The payment was modified by another request. Please refresh and try again.',
        );
    }
}

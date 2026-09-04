<?php

namespace Modules\Payment\Exceptions;

use RuntimeException;

class PaymentCannotBeMarkedAsPaidException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The payment cannot be marked as paid in its current state.',
        );
    }
}

<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class InactivePlanException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The selected subscription plan is inactive.',
        );
    }
}

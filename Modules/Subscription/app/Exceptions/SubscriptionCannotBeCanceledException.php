<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class SubscriptionCannotBeCanceledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The subscription cannot be canceled in its current state.',
        );
    }
}
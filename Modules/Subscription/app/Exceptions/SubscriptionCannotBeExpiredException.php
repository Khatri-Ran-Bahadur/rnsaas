<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class SubscriptionCannotBeExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The subscription cannot be expired at this time.',
        );
    }
}
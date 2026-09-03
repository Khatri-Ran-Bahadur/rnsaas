<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class SubscriptionCannotBeRenewedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The subscription cannot be renewed in its current state.',
        );
    }
}
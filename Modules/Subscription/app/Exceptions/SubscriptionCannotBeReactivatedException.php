<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class SubscriptionCannotBeReactivatedException extends RuntimeException
{
    public function __construct(
        string $message = 'The subscription cannot be reactivated in its current state.',
    ) {
        parent::__construct($message);
    }
}

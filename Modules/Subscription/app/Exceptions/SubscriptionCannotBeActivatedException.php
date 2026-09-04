<?php

namespace Modules\Subscription\Exceptions;

use RuntimeException;

class SubscriptionCannotBeActivatedException extends RuntimeException
{
    public function __construct(
        string $message = 'The subscription cannot be activated from its current state.',
    ) {
        parent::__construct($message);
    }
}

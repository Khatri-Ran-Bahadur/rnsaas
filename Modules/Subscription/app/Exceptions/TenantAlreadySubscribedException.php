<?php

namespace Modules\Subscription\Exceptions;

use Exception;

class TenantAlreadySubscribedException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'The tenant already has an active subscription.',
        );
    }
}

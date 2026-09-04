<?php

namespace Modules\Tenancy\Domain\Exceptions;

use RuntimeException;

final class TenantMembershipConcurrencyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The membership was modified by another request. Please refresh and try again.',
        );
    }
}
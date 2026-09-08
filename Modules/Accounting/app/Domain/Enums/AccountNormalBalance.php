<?php

namespace Modules\Accounting\Domain\Enums;

enum AccountNormalBalance: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

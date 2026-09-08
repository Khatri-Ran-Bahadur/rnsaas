<?php

namespace Modules\Accounting\Domain\Enums;

enum JournalLineType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }
}

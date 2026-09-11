<?php

namespace Modules\Accounting\Domain\Enums;

enum PurchaseBillStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case POSTED = 'posted';
    case VOID = 'void';

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }
}

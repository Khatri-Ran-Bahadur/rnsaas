<?php

namespace Modules\Accounting\Domain\Enums;

enum CustomerPaymentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Void = 'void';
}

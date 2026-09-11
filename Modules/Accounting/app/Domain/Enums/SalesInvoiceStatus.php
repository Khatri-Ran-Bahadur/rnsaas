<?php

namespace Modules\Accounting\Domain\Enums;

enum SalesInvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Posted = 'posted';
    case Void = 'void';
}

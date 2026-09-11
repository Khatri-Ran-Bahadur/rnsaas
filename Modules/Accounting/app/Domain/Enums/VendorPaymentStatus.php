<?php

namespace Modules\Accounting\Domain\Enums;

enum VendorPaymentStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
}

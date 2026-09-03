<?php

namespace Modules\Payment\Enums;

enum PaymentType: string
{
    case Subscription = 'subscription';
    case Renewal = 'renewal';
    case Addon = 'addon';
    case OneTime = 'one_time';
}
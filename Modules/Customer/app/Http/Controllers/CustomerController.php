<?php

namespace Modules\Customer\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CustomerController
{
    public function create(): Response
    {
        return Inertia::render('Customer/Create');
    }
}

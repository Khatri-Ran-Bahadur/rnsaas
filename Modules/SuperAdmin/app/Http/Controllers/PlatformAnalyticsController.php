<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Services\PlatformAnalyticsService;

class PlatformAnalyticsController
{
    public function __construct(
        private readonly PlatformAnalyticsService $analytics,
    ) {}

    public function index(Request $request): Response
    {
        $from = $request->date('from');
        $to = $request->date('to');

        return Inertia::render('Analytics/Index', [
            'analytics' => $this->analytics->overview(
                from: $from,
                to: $to,
            ),
            'filters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }
}

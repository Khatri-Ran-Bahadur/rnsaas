<?php

namespace Modules\Accounting\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingModuleEnabled
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->currentTenant->has()) {
            $tenant = $this->currentTenant->get();

            if (method_exists($tenant, 'isModuleEnabled') && ! $tenant->isModuleEnabled('accounting')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'The Accounting module is disabled for this organization.',
                    ], 403);
                }

                return redirect()->route('admin.dashboard')->with('error', 'The Accounting module is currently disabled for your organization.');
            }
        }

        return $next($request);
    }
}

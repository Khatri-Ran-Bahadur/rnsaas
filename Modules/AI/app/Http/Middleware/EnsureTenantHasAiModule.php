<?php

namespace Modules\AI\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasAiModule
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->currentTenant->get();

        if (! $tenant) {
            abort(401);
        }

        // Check if AI module is enabled via subscription package / tenant settings
        if (! $tenant->isModuleEnabled('ai')) {
            if ($request->expectsJson() || $request->is('admin/ai/*') || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'is_module_disabled' => true,
                    'role' => 'assistant',
                    'content' => '🔒 **AI Assistant Package Upgrade Required**\n\nतपाईंको हालको सब्स्क्रिप्सन प्लानमा AI Copilot मोड्युल सक्रिय गरिएको छैन। कृपया **Subscription Plans** मा गएर AI सुविधा भएको प्याकेज सक्रिय गर्नुहोस्।',
                    'message' => 'AI Module is not active in your subscription plan. Please upgrade your package.',
                ], 403);
            }

            return redirect()->route('admin.subscription.plans')
                ->with('error', 'AI Assistant module is not included in your current subscription plan. Please upgrade to access AI features.');
        }

        return $next($request);
    }
}

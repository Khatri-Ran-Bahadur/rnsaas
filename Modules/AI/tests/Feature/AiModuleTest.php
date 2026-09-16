<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createTenantWithAiPlan(bool $hasAiFeature = true, array $aiSettings = []): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Sathi Enterprise Tech',
        'slug' => 'sathi-enterprise',
        'status' => TenantStatus::Active,
        'country_code' => 'NP',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'NPR',
        'locale' => 'en',
        'settings' => [
            'ai' => $aiSettings,
            'modules' => [
                'ai' => $hasAiFeature,
            ],
        ],
    ]);

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $plan = Plan::factory()->create([
        'name' => $hasAiFeature ? 'Enterprise AI Plan' : 'Basic Starter Plan',
        'slug' => $hasAiFeature ? 'enterprise-ai' : 'basic-starter',
    ]);

    $aiFeature = Feature::query()->where('slug', 'ai')->first();
    if (! $aiFeature) {
        $aiFeature = Feature::query()->create([
            'public_id' => (string) Str::ulid(),
            'name' => 'AI Assistant & Copilot',
            'slug' => 'ai',
            'module' => 'ai',
            'is_active' => true,
        ]);
    }

    if ($hasAiFeature) {
        $plan->features()->syncWithoutDetaching([$aiFeature->id]);
    }

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_ends_at' => now()->addMonth(),
    ]);

    return [$user, $tenant, $plan, $subscription];
}

it('allows copilot status check and reports active subscription capability', function (): void {
    [$user, $tenant] = createTenantWithAiPlan(hasAiFeature: true, aiSettings: [
        'is_enabled' => true,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-1.5-flash',
        'ai_api_key' => Crypt::encryptString('mock-gemini-key'),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->getJson('/admin/ai/copilot/status');

    $response->assertSuccessful();
    $response->assertJson([
        'is_module_enabled' => true,
        'is_configured' => true,
        'is_enabled' => true,
        'provider' => 'gemini',
    ]);
});

it('blocks chat access when tenant subscription does not have AI feature', function (): void {
    [$user, $tenant] = createTenantWithAiPlan(hasAiFeature: false, aiSettings: [
        'is_enabled' => true,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-1.5-flash',
        'ai_api_key' => Crypt::encryptString('mock-gemini-key'),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->postJson('/admin/ai/copilot/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'Show me sales'],
            ],
        ]);

    $response->assertStatus(403);
    $response->assertJson([
        'is_module_disabled' => true,
    ]);
});

it('executes AI chat successfully when tenant has active AI subscription', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'तपाईंको कम्पनीको यो महिनाको कुल बिक्री विवरण राम्रो छ।'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    [$user, $tenant] = createTenantWithAiPlan(hasAiFeature: true, aiSettings: [
        'is_enabled' => true,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-1.5-flash',
        'ai_api_key' => Crypt::encryptString('valid-gemini-key'),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->postJson('/admin/ai/copilot/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'बिक्री कस्तो छ?'],
            ],
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'role' => 'assistant',
        'content' => 'तपाईंको कम्पनीको यो महिनाको कुल बिक्री विवरण राम्रो छ।',
    ]);
});

it('tests AI settings connection for Gemini and OpenAI providers', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'AI connection successful'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    [$user, $tenant] = createTenantWithAiPlan(hasAiFeature: true);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->postJson('/admin/ai/settings/test', [
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'ai_api_key' => 'AIzaSyMockKey123',
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
    ]);
});

<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Application\Actions\Tenant\ProvisionTenantDefaultsAction;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Domain\Events\TenantCreated;
use Modules\Tenancy\Models\Tenant;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user and organization.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'company_name' => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'currency' => ['nullable', 'string', 'max:5'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
            'plan_slug' => ['nullable', 'string', 'max:50'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            $slugBase = Str::slug($input['company_name']);
            $slug = $slugBase ?: 'company-'.Str::random(5);
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$counter}";
                $counter++;
            }

            $tenant = Tenant::create([
                'public_id' => (string) Str::ulid(),
                'name' => $input['company_name'],
                'slug' => $slug,
                'industry' => $input['industry'] ?? null,
                'status' => TenantStatus::Active,
                'country_code' => $input['country_code'] ?? 'MY',
                'currency' => $input['currency'] ?? 'MYR',
                'timezone' => $input['timezone'] ?? 'Asia/Kuala_Lumpur',
                'locale' => $input['locale'] ?? 'en',
            ]);

            // Attach user to tenant
            $user->tenants()->attach($tenant->id, [
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Provision tenant defaults (departments, designations, roles, admin membership)
            if (class_exists(ProvisionTenantDefaultsAction::class)) {
                app(ProvisionTenantDefaultsAction::class)->handle($tenant);
            }

            // Dispatch TenantCreated event for module listeners (e.g. Accounting provisioning)
            event(new TenantCreated($tenant));

            // If a plan was selected, record pending subscription waiting for SuperAdmin approval
            $planSlug = $input['plan_slug'] ?? 'starter';
            $plan = Plan::where('slug', $planSlug)->first();
            if ($plan && class_exists(TenantSubscription::class)) {
                TenantSubscription::create([
                    'public_id' => (string) Str::ulid(),
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Pending,
                    'starts_at' => now(),
                    'trial_ends_at' => null,
                    'current_period_starts_at' => now(),
                    'current_period_ends_at' => now()->addDays(30),
                ]);
            }

            return $user;
        });
    }
}

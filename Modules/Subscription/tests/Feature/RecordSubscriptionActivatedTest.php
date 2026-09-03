<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Models\AuditLog;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Listeners\RecordSubscriptionActivated;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('records subscription activation in audit log', function () {
    $tenant = Tenant::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $subscription->setRawAttributes([
        ...$subscription->getAttributes(),
        'status' => SubscriptionStatus::Pending->value,
    ]);

    $subscription->setAttribute(
        'status',
        SubscriptionStatus::Active,
    );

    $subscription->syncChanges();

    $event = new SubscriptionActivated($subscription);

    app(RecordSubscriptionActivated::class)
        ->handle($event);

    $audit = AuditLog::query()
        ->where('event', 'subscription.activated')
        ->where('auditable_id', $subscription->id)
        ->firstOrFail();

    expect($audit->tenant_id)
        ->toBe($tenant->id);

    expect($audit->old_values['status'])
        ->toBe(SubscriptionStatus::Pending->value);

    expect($audit->new_values['status'])
        ->toBe(SubscriptionStatus::Active->value);

    expect($audit->metadata['module'])
        ->toBe('subscription');

    expect($audit->metadata['subscription_public_id'])
        ->toBe($subscription->public_id);
});
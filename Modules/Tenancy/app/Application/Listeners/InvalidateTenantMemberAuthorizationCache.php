<?php

namespace Modules\Tenancy\Application\Listeners;

use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;

final class InvalidateTenantMemberAuthorizationCache
{
    public function __construct(
        private readonly OrganizationAuthorizationService $authorizationService,
    ) {}

    public function handle(TenantMembershipStatusChanged $event): void
    {
        $this->authorizationService->invalidateUser(
            $event->membership->tenant_id,
            $event->membership->user_id,
        );
    }
}

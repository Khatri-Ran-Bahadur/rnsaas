<?php

namespace Modules\Tenancy\Domain\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;

final class TenantMembershipStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly TenantMembership $membership,
        public readonly TenantMembershipStatus $oldStatus,
        public readonly TenantMembershipStatus $newStatus,
        public readonly User $changedBy,
    ) {}
}

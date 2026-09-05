<?php

namespace Modules\Admin\DTOs;

final readonly class OrganizationDashboardData
{
    public function __construct(
        public array $tenant,
        public array $members,
        public array $subscription,
    ) {}

    public function toArray(): array
    {
        return [
            'tenant' => $this->tenant,
            'members' => $this->members,
            'subscription' => $this->subscription,
        ];
    }
}

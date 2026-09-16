<?php

namespace Modules\Admin\DTOs;

final readonly class OrganizationDashboardData
{
    public function __construct(
        public array $tenant,
        public array $members,
        public array $subscription,
        public array $accounting = [],
        public array $pos = [],
        public array $inventory = [],
        public array $operations = [],
    ) {}

    public function toArray(): array
    {
        return [
            'tenant' => $this->tenant,
            'members' => $this->members,
            'subscription' => $this->subscription,
            'accounting' => $this->accounting,
            'pos' => $this->pos,
            'inventory' => $this->inventory,
            'operations' => $this->operations,
        ];
    }
}

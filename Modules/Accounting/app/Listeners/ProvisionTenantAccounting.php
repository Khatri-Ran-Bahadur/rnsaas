<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Application\Actions\Accounting\ProvisionChartOfAccountsAction;
use Modules\Tenancy\Domain\Events\TenantCreated;

final class ProvisionTenantAccounting
{
    public function __construct(
        private readonly ProvisionChartOfAccountsAction $action,
    ) {}

    public function handle(
        TenantCreated $event,
    ): void {
        $this->action->execute(
            $event->tenant,
        );
    }
}

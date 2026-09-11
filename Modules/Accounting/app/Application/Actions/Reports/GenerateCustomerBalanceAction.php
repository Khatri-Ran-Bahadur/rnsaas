<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Customer;

final class GenerateCustomerBalanceAction
{
    public function execute(
        Customer $customer,
        CurrentTenant $currentTenant,
    ): array {
        abort_unless(
            $customer->tenant_id === $currentTenant->id(),
            404
        );

        $receivableAccountId = $customer->receivable_account_id;

        if ($receivableAccountId === null) {
            return [
                'customer_id' => $customer->public_id,
                'customer_name' => $customer->name,
                'balance' => '0.000000',
            ];
        }

        $result = DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id'
            )
            ->where('journal_lines.tenant_id', $currentTenant->id())
            ->where('journal_entries.tenant_id', $currentTenant->id())
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $receivableAccountId)
            ->selectRaw(
                "COALESCE(SUM(
                    CASE
                        WHEN journal_lines.line_type = 'debit'
                        THEN journal_lines.amount
                        ELSE -journal_lines.amount
                    END
                ), 0) AS balance"
            )
            ->value('balance');

        return [
            'customer_id' => $customer->public_id,
            'customer_name' => $customer->name,
            'balance' => number_format(
                (float) $result,
                6,
                '.',
                ''
            ),
        ];
    }
}

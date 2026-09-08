<?php

namespace Modules\Accounting\Application\Actions\Journal;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Application\Services\Journal\JournalValidationService;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Domain\Enums\JournalLineType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;

final class CreateJournalEntryAction
{
    public function __construct(
        private readonly AccountingPeriodResolver $periodResolver,
        private readonly JournalValidationService $validator,
    ) {}

    /**
     * @param array<int, array{
     *     account_id:int,
     *     line_type:JournalLineType|string,
     *     amount:numeric,
     *     description?:string|null
     * }> $lines
     */
    public function execute(
        int $tenantId,
        string $entryNumber,
        CarbonInterface $entryDate,
        string $description,
        array $lines,
        ?int $createdBy = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null,
    ): JournalEntry {
        $this->validator->validateBalance($lines);

        return DB::transaction(function () use (
            $tenantId,
            $entryNumber,
            $entryDate,
            $description,
            $lines,
            $createdBy,
            $referenceType,
            $referenceId,
            $idempotencyKey,
        ): JournalEntry {
            if ($idempotencyKey !== null) {
                $existing = JournalEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing->load('lines');
                }
            }

            $period = $this->periodResolver->resolve(
                $tenantId,
                $entryDate,
            );

            $accountIds = collect($lines)
                ->pluck('account_id')
                ->unique()
                ->values();

            $accounts = Account::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id');

            if ($accounts->count() !== $accountIds->count()) {
                throw new InvalidArgumentException(
                    'One or more accounts do not belong to the current tenant.',
                );
            }

            foreach ($accounts as $account) {
                if (! $account->is_active) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Account "%s" is inactive.',
                            $account->code,
                        ),
                    );
                }

                if (! $account->is_postable) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Account "%s" is not postable.',
                            $account->code,
                        ),
                    );
                }
            }

            $journal = JournalEntry::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'fiscal_year_id' => $period->fiscal_year_id,
                'accounting_period_id' => $period->id,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => JournalEntryStatus::DRAFT,
                'idempotency_key' => $idempotencyKey,
                'created_by' => $createdBy,
            ]);

            foreach (array_values($lines) as $index => $line) {
                $type = $line['line_type'];

                if (is_string($type)) {
                    $type = JournalLineType::from($type);
                }

                $journal->lines()->create([
                    'public_id' => (string) Str::uuid(),
                    'tenant_id' => $tenantId,
                    'account_id' => $line['account_id'],
                    'line_number' => $index + 1,
                    'line_type' => $type,
                    'amount' => $line['amount'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $journal->load('lines');
        });
    }
}

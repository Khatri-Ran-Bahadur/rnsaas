<?php

namespace Modules\Accounting\Http\Requests\JournalEntries;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Accounting\Domain\Enums\JournalLineType;

class StoreJournalEntryRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'entry_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('journal_entries', 'entry_number')
                    ->where('tenant_id', $tenantId),
            ],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('is_postable', true),
            ],
            'lines.*.line_type' => [
                'required',
                Rule::enum(JournalLineType::class),
            ],
            'lines.*.amount' => ['required', 'numeric', 'gt:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $debitTotal = '0.000000';
                $creditTotal = '0.000000';

                foreach ($this->input('lines', []) as $line) {
                    if (! isset($line['line_type'], $line['amount'])) {
                        return;
                    }

                    if ($line['line_type'] === JournalLineType::DEBIT->value) {
                        $debitTotal = bcadd($debitTotal, (string) $line['amount'], 6);
                    }

                    if ($line['line_type'] === JournalLineType::CREDIT->value) {
                        $creditTotal = bcadd($creditTotal, (string) $line['amount'], 6);
                    }
                }

                if (bccomp($debitTotal, $creditTotal, 6) !== 0) {
                    $validator->errors()->add(
                        'lines',
                        'Debit total and credit total must be equal.',
                    );
                }
            },
        ];
    }
}

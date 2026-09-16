<?php

namespace Modules\Accounting\Http\Controllers\JournalEntries;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalEntryData;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalLineData;
use Modules\Accounting\Http\Requests\JournalEntries\StoreJournalEntryRequest;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): InertiaResponse {
        $tenantId = $currentTenant->id();

        $journals = JournalEntry::query()
            ->where('tenant_id', $tenantId)
            ->with('lines.account:id,code,name')
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest('entry_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (JournalEntry $journal): array => [
                'id' => $journal->public_id,
                'entry_number' => $journal->entry_number,
                'entry_date' => $journal->entry_date->toDateString(),
                'description' => $journal->description,
                'status' => [
                    'value' => $journal->status->value,
                    'label' => $journal->status->label(),
                ],
                'debit_total' => $journal->lines
                    ->where('line_type.value', 'debit')
                    ->sum('amount'),
                'credit_total' => $journal->lines
                    ->where('line_type.value', 'credit')
                    ->sum('amount'),
            ]);

        return Inertia::render('Accounting/JournalEntries/Index', [
            'journals' => $journals,
            'filters' => [
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    public function create(CurrentTenant $currentTenant): InertiaResponse
    {
        $accounts = Account::query()
            ->where('tenant_id', $currentTenant->id())
            ->active()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/JournalEntries/Create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(
        StoreJournalEntryRequest $request,
        CreateJournalEntryAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $lines = collect($request->validated('lines'))
            ->values()
            ->map(fn (array $line, int $index) => new CreateJournalLineData(
                accountId: $line['account_id'],
                lineNumber: $index + 1,
                lineType: $line['line_type'],
                amount: $line['amount'],
                description: $line['description'] ?? null,
            ))
            ->all();

        $action->execute(new CreateJournalEntryData(
            tenantId: $currentTenant->id(),
            fiscalYearId: null,
            accountingPeriodId: null,
            entryNumber: $request->string('entry_number')->toString(),
            entryDate: $request->date('entry_date'),
            description: $request->string('description')->toString(),
            createdBy: $request->user()->id,
            lines: $lines,
        ));

        return redirect()
            ->route('admin.accounting.journal-entries.index')
            ->with('success', 'Journal entry saved as draft.');
    }

    public function post(
        JournalEntry $journal,
        PostJournalEntryAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse {
        abort_unless($journal->tenant_id === $currentTenant->id(), 404);

        $action->execute($journal, $request->user()->id);

        return back()->with('success', 'Journal entry posted successfully.');
    }
}

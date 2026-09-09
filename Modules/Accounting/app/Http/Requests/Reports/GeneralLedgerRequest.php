<?php

namespace Modules\Accounting\Http\Requests\Reports;

class GeneralLedgerRequest extends ReportDateRangeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'account_id' => [
                'required',
                'integer',
                'exists:accounting_accounts,id',
            ],
        ]);
    }
}

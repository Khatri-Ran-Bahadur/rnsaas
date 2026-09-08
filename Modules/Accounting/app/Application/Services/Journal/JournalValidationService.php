<?php

namespace Modules\Accounting\Application\Services\Journal;

use Modules\Accounting\Domain\Enums\JournalLineType;
use Modules\Accounting\Domain\Exceptions\UnbalancedJournalException;

final class JournalValidationService
{
    /**
     * @param array<int, array{
     *     account_id:int,
     *     line_type:JournalLineType|string,
     *     amount:numeric,
     *     description?:string|null
     * }> $lines
     */
    public function validateBalance(
        array $lines,
    ): void {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException(
                'A journal must contain at least two lines.',
            );
        }

        $debitTotal = '0.000000';
        $creditTotal = '0.000000';

        foreach ($lines as $line) {
            $amount = number_format(
                (float) $line['amount'],
                6,
                '.',
                '',
            );

            if ((float) $amount <= 0) {
                throw new \InvalidArgumentException(
                    'Journal line amount must be greater than zero.',
                );
            }

            $type = $line['line_type'];

            if (is_string($type)) {
                $type = JournalLineType::from($type);
            }

            if ($type === JournalLineType::DEBIT) {
                $debitTotal = bcadd(
                    $debitTotal,
                    $amount,
                    6,
                );
            } else {
                $creditTotal = bcadd(
                    $creditTotal,
                    $amount,
                    6,
                );
            }
        }

        if (bccomp($debitTotal, $creditTotal, 6) !== 0) {
            throw new UnbalancedJournalException(
                $debitTotal,
                $creditTotal,
            );
        }
    }
}

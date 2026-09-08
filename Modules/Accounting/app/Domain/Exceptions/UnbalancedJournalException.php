<?php

namespace Modules\Accounting\Domain\Exceptions;

use RuntimeException;

final class UnbalancedJournalException extends RuntimeException
{
    public function __construct(
        public readonly string $debitTotal,
        public readonly string $creditTotal,
    ) {
        parent::__construct(
            sprintf(
                'Journal is not balanced. Debit: %s, Credit: %s.',
                $debitTotal,
                $creditTotal,
            ),
        );
    }
}

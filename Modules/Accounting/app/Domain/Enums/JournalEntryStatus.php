<?php

namespace Modules\Accounting\Domain\Enums;

enum JournalEntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::REVERSED => 'Reversed',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this === self::POSTED;
    }

    public function isReversed(): bool
    {
        return $this === self::REVERSED;
    }
}

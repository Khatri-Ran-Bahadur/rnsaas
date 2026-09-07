<?php

namespace Modules\HRM\Application\Actions\Shifts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\HRM\Models\Shift;

final class GetShiftAction
{
    public function execute(
        int $tenantId,
        string $publicId,
    ): Shift {
        $shift = Shift::query()
            ->forTenant($tenantId)
            ->where('public_id', $publicId)
            ->first();

        if (! $shift) {
            throw (new ModelNotFoundException)
                ->setModel(Shift::class);
        }

        return $shift;
    }
}

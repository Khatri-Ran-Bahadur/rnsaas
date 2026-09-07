<?php

namespace Modules\HRM\Application\Actions\Shifts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\HRM\Models\Shift;

final class ToggleShiftStatusAction
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

        $shift->update([
            'is_active' => ! $shift->is_active,
        ]);

        return $shift->refresh();
    }
}

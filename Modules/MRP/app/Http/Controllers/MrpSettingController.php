<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpSetting;

class MrpSettingController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $mrpSetting = MrpSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'auto_reserve_materials' => true,
                'allow_negative_raw_materials' => false,
                'require_qa_approval_before_fg' => true,
                'default_scrap_percentage' => 2.00,
                'mrp_planning_horizon_days' => 30,
                'default_overhead_allocation_rate' => 15.00,
                'track_lot_genealogy' => true,
                'require_manager_override_for_scrap' => true,
            ]
        );

        $settings = [
            'auto_reserve_materials' => (bool) $mrpSetting->auto_reserve_materials,
            'allow_negative_raw_materials' => (bool) $mrpSetting->allow_negative_raw_materials,
            'require_qa_approval_before_fg' => (bool) $mrpSetting->require_qa_approval_before_fg,
            'default_scrap_percentage' => (float) $mrpSetting->default_scrap_percentage,
            'mrp_planning_horizon_days' => (int) $mrpSetting->mrp_planning_horizon_days,
            'default_overhead_allocation_rate' => (float) $mrpSetting->default_overhead_allocation_rate,
            'track_lot_genealogy' => (bool) $mrpSetting->track_lot_genealogy,
            'require_manager_override_for_scrap' => (bool) $mrpSetting->require_manager_override_for_scrap,
        ];

        return Inertia::render('MRP/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'auto_reserve_materials' => ['boolean'],
            'allow_negative_raw_materials' => ['boolean'],
            'require_qa_approval_before_fg' => ['boolean'],
            'default_scrap_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'mrp_planning_horizon_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_overhead_allocation_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'track_lot_genealogy' => ['boolean'],
            'require_manager_override_for_scrap' => ['boolean'],
        ]);

        MrpSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            $validated
        );

        return redirect()->route('admin.mrp.settings.index')->with('success', 'MRP settings updated successfully.');
    }
}

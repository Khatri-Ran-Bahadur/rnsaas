<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MrpReportController extends Controller
{
    public function index(Request $request): Response
    {
        $reportTypes = [
            ['id' => 'production_summary', 'title' => 'Production Summary Report', 'desc' => 'Planned vs actual output by product, work order, and period.'],
            ['id' => 'material_consumption', 'title' => 'Material Consumption & Shortage', 'desc' => 'Detailed raw material usage, reserved vs issued vs scrap.'],
            ['id' => 'scrap_analysis', 'title' => 'Scrap & Wastage Analysis', 'desc' => 'Defect breakdown by reason code, work center, and cost loss.'],
            ['id' => 'work_center_utilization', 'title' => 'Work Center & Machine OEE', 'desc' => 'Capacity utilization, machine downtime, and operator efficiency.'],
            ['id' => 'production_cost_variance', 'title' => 'Cost Variance & Standard Costing', 'desc' => 'Actual vs estimated cost breakdown across materials, labor, and machine overhead.'],
            ['id' => 'genealogy_traceability', 'title' => 'Batch / Lot Traceability Audit', 'desc' => 'Forward and backward lot tracing for regulatory compliance and recalls.'],
        ];

        return Inertia::render('MRP/Reports/Index', [
            'reportTypes' => $reportTypes,
        ]);
    }
}

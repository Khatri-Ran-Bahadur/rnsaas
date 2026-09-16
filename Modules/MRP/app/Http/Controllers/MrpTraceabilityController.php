<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MrpTraceabilityController extends Controller
{
    public function index(Request $request): Response
    {
        $genealogyTree = [
            'type' => 'Finished Good',
            'label' => 'Artisan Sourdough Loaf 500g (Batch LOT-BAK-20260911-01)',
            'sku' => 'BAK-SRD-500',
            'qty' => '180 Loaves',
            'date' => '2026-09-11 11:30',
            'location' => 'Finished Goods Chilled Staging',
            'children' => [
                [
                    'type' => 'Production Run',
                    'label' => 'Run #RUN-2026-0089',
                    'status' => 'In Progress',
                    'operator' => 'Thomas Henderson',
                    'work_center' => 'Oven Deck Line A',
                    'children' => [
                        [
                            'type' => 'Work Order',
                            'label' => 'WO-2026-0042 (Planned: 300 Loaves)',
                            'status' => 'In Progress',
                            'priority' => 'High',
                            'children' => [
                                [
                                    'type' => 'BOM Version',
                                    'label' => 'BOM-2026-001 (v2.1 Approved)',
                                    'status' => 'Active',
                                    'children' => [
                                        [
                                            'type' => 'Raw Material Batch',
                                            'label' => 'Organic Rye Flour (Batch BATCH-FLOUR-9941)',
                                            'sku' => 'RM-FLOUR-RYE',
                                            'consumed_qty' => '95.00 Kg',
                                            'supplier' => 'BioGrain Millers Ltd (PO-2026-0412)',
                                            'received_date' => '2026-08-25',
                                            'expiry_date' => '2027-02-25',
                                            'qc_status' => 'Passed (QC-2026-0082)',
                                        ],
                                        [
                                            'type' => 'Raw Material Batch',
                                            'label' => 'Artisan Sourdough Mother Starter (Fermenter Vat 2)',
                                            'sku' => 'RM-STARTER-100',
                                            'consumed_qty' => '18.00 Kg',
                                            'supplier' => 'Internal Cultivation',
                                            'qc_status' => 'Passed pH 3.8',
                                        ],
                                        [
                                            'type' => 'Raw Material Batch',
                                            'label' => 'Fine Sea Salt (Batch BATCH-SALT-101)',
                                            'sku' => 'RM-SALT-SEA',
                                            'consumed_qty' => '2.70 Kg',
                                            'supplier' => 'Atlantic Salt Co (PO-2026-0390)',
                                            'qc_status' => 'Passed',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'Quality Inspection',
                    'label' => 'QC-2026-0112 (Internal Temp 97.2°C)',
                    'status' => 'Passed',
                    'inspector' => 'Helena Vance',
                    'date' => '2026-09-11 10:15',
                ],
                [
                    'type' => 'Stock Movement & Ledger',
                    'label' => 'Inv Move #STK-2026-0982 (+180 into FG Staging)',
                    'status' => 'Posted to Inventory Ledger',
                    'journal' => 'GL #JRN-2026-4421 (WIP -> FG Inventory)',
                ],
            ],
        ];

        return Inertia::render('MRP/Traceability/Index', [
            'genealogy' => $genealogyTree,
        ]);
    }
}

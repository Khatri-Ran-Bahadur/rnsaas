<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\MRP\Database\Seeders\MrpDatabaseSeeder;
use Modules\MRP\Models\MrpBom;
use Modules\MRP\Models\MrpQualityInspection;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\MRP\Models\MrpWorkOrder;
use Modules\POS\Database\Seeders\PosDatabaseSeeder;
use Modules\POS\Models\PosKitchenTicket;
use Modules\POS\Models\PosOrder;
use Modules\POS\Models\PosShift;
use Modules\POS\Models\PosTable;
use Modules\Tenancy\Models\Tenant;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->user->tenants()->attach($this->tenant->id, ['status' => 'active']);

    $this->seed(PosDatabaseSeeder::class);
    $this->seed(MrpDatabaseSeeder::class);
});

test('POS models have dynamic seeded records and relationships', function (): void {
    expect(PosShift::count())->toBeGreaterThan(0)
        ->and(PosTable::count())->toBeGreaterThan(0)
        ->and(PosKitchenTicket::count())->toBeGreaterThan(0)
        ->and(PosOrder::count())->toBeGreaterThan(0);

    $ticket = PosKitchenTicket::with('items')->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->items)->not->toBeEmpty();

    $table = PosTable::first();
    expect($table)->not->toBeNull()
        ->and($table->status)->toBeIn(['available', 'occupied', 'reserved', 'cleaning']);
});

test('POS controllers return dynamic database records via Inertia', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos/shifts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Shifts/Index')
            ->has('shifts')
            ->has('stats')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos/kds')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Kitchen/Kds')
            ->has('tickets')
            ->has('stations')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos/floor-plan')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Restaurant/FloorPlan')
            ->has('tables')
            ->has('floors')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos/orders')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Orders/History')
            ->has('orders')
            ->has('metrics')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos/reports')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Reports/Index')
            ->has('summary')
            ->has('summary.top_selling_items')
            ->has('categorySales')
            ->has('paymentBreakdown')
            ->has('hourlySales')
            ->has('cashierPerformance')
        );
});

test('MRP models have dynamic seeded records and relationships', function (): void {
    expect(MrpWorkCenter::count())->toBeGreaterThan(0)
        ->and(MrpBom::count())->toBeGreaterThan(0)
        ->and(MrpWorkOrder::count())->toBeGreaterThan(0)
        ->and(MrpQualityInspection::count())->toBeGreaterThan(0);

    $bom = MrpBom::with('items')->first();
    expect($bom)->not->toBeNull()
        ->and($bom->items)->not->toBeEmpty();

    $workOrder = MrpWorkOrder::first();
    expect($workOrder)->not->toBeNull()
        ->and($workOrder->wo_number)->toStartWith('WO-');
});

test('MRP controllers return dynamic database records via Inertia', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/mrp')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MRP/Dashboard')
            ->has('stats')
            ->has('kpis')
            ->has('recentWorkOrders')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/mrp/bom')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MRP/BOM/Index')
            ->has('boms')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/mrp/work-centers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MRP/WorkCenters/Index')
            ->has('workCenters')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/mrp/work-orders')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MRP/WorkOrders/Index')
            ->has('workOrders')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/mrp/quality')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MRP/Quality/Index')
            ->has('inspections')
            ->has('summary')
        );
});

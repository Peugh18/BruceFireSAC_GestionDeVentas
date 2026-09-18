<?php

use App\Models\ElectronicDocument;
use App\Models\Equipment;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\ServiceOrder;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard route redirects guests to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard loads without error for an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('kpis')
            ->has('ventas_mensuales', 6)
            ->has('servicios_por_tipo')
            ->has('proximos_vencimientos')
            ->has('ordenes_recientes')
            ->has('stock_critico')
        );
});

test('dashboard kpis compute correct numbers from known data', function () {
    $user = User::factory()->create();

    // Ventas del mes: two valid sales in the current month, one anulada (excluded).
    Sale::factory()->create(['estado' => 'completada', 'fecha' => now()->startOfMonth()->toDateString(), 'total' => 500]);
    Sale::factory()->create(['estado' => 'completada', 'fecha' => now()->toDateString(), 'total' => 300]);
    Sale::factory()->create(['estado' => 'anulada', 'fecha' => now()->toDateString(), 'total' => 999]);
    // Outside the current month: excluded.
    Sale::factory()->create(['estado' => 'completada', 'fecha' => now()->subMonths(2)->toDateString(), 'total' => 777]);

    // Facturacion del mes: only the accepted electronic document counts.
    $billedSale = Sale::factory()->create(['estado' => 'completada', 'fecha' => now()->toDateString(), 'total' => 236]);
    ElectronicDocument::factory()->aceptado()->create(['sale_id' => $billedSale->id, 'fecha_envio' => now()]);

    $pendingDocSale = Sale::factory()->create(['total' => 100]);
    ElectronicDocument::factory()->create(['sale_id' => $pendingDocSale->id, 'estado' => 'pendiente']);

    // Por cobrar: only installments with a pending balance. Their sales are
    // dated outside the current month so they do not affect ventas_mes.
    $creditSale = Sale::factory()->create(['fecha' => now()->subMonths(2)->toDateString()]);
    SaleInstallment::factory()->create(['sale_id' => $creditSale->id, 'monto_pendiente' => 150]);
    SaleInstallment::factory()->create(['sale_id' => $creditSale->id, 'monto_pendiente' => 50]);
    SaleInstallment::factory()->create(['sale_id' => $creditSale->id, 'monto_pendiente' => 0, 'estado' => 'pagado']);

    // Servicios pendientes: every non-cerrado order counts.
    ServiceOrder::factory()->create(['estado' => 'en_proceso']);
    ServiceOrder::factory()->create(['estado' => 'pendiente_recepcion']);
    ServiceOrder::factory()->create(['estado' => 'cerrado']);

    // Equipos por vencer: proxima_atencion within the next 30 days.
    Equipment::factory()->create(['proxima_atencion' => now()->addDays(10)->toDateString()]);
    Equipment::factory()->create(['proxima_ph' => now()->addDays(5)->toDateString()]);
    Equipment::factory()->create(['proxima_atencion' => now()->addDays(90)->toDateString()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();

    $kpis = $response->viewData('page')['props']['kpis'];

    expect($kpis['ventas_mes'])->toBe(500.0 + 300.0 + 236.0 + 100.0);
    expect($kpis['facturacion_mes'])->toBe(236.0);
    expect($kpis['por_cobrar'])->toBe(200.0);
    expect($kpis['servicios_pendientes'])->toBe(2);
    expect($kpis['equipos_por_vencer'])->toBe(2);
});

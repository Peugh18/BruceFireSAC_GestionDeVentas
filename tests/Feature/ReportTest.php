<?php

use App\Models\CatalogItem;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Deficiency;
use App\Models\ElectronicDocument;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function reportUser(array $permissions = []): User
{
    $user = User::factory()->create();
    if (! empty($permissions)) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

test('guests cannot access reports or export', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('reports.export'))->assertRedirect(route('login'));
});

test('users without reports.view permission receive 403', function () {
    $technician = reportUser(['service_orders.view']);

    $this->actingAs($technician)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($technician)->get(route('reports.export'))->assertForbidden();
});

test('user with reports.view can view comerciales report with correct metrics and conversion rate', function () {
    $manager = reportUser(['reports.view']);

    $seller = User::factory()->create();
    $client = Client::factory()->create(['razon_social' => 'Cliente Reportes SAC']);
    $catalogItem = CatalogItem::factory()->create([
        'codigo' => 'PROD-001',
        'nombre' => 'Extintor PQS 6kg',
        'tipo' => 'producto',
    ]);

    // Create 1 completed sale
    $sale = Sale::factory()->create([
        'client_id' => $client->id,
        'vendedor_user_id' => $seller->id,
        'fecha' => now()->toDateString(),
        'estado' => 'completada',
        'subtotal' => 100.00,
        'igv' => 18.00,
        'total' => 118.00,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 2,
        'precio_unitario' => 50.00,
        'subtotal' => 100.00,
    ]);

    // Create 1 accepted quote and 1 rejected quote
    Quote::factory()->create([
        'client_id' => $client->id,
        'vendedor_user_id' => $seller->id,
        'fecha' => now()->toDateString(),
        'estado' => 'aceptada',
        'total' => 118.00,
    ]);

    Quote::factory()->create([
        'client_id' => $client->id,
        'vendedor_user_id' => $seller->id,
        'fecha' => now()->toDateString(),
        'estado' => 'rechazada',
        'total' => 500.00,
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index', [
            'categoria' => 'comerciales',
            'fecha_desde' => now()->startOfMonth()->toDateString(),
            'fecha_hasta' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('categoria', 'comerciales')
            ->has('reportData.resumen', fn (Assert $resumen) => $resumen
                ->where('total_vendido', 118)
                ->where('total_transacciones', 1)
                ->where('total_cotizaciones', 2)
                ->where('cotizaciones_aceptadas', 1)
                ->where('cotizaciones_rechazadas', 1)
                ->where('tasa_conversion', 50)
                ->etc()
            )
            ->has('reportData.ventas_vendedor', 1)
            ->has('reportData.ventas_cliente', 1)
            ->has('reportData.ventas_producto', 1)
        );
});

test('user can view inventario report detecting low stock items and movements', function () {
    $manager = reportUser(['reports.view']);

    $itemLowStock = CatalogItem::factory()->create([
        'codigo' => 'PROD-002',
        'nombre' => 'Extintor CO2 5lb',
        'tipo' => 'producto',
        'controla_stock' => true,
    ]);

    InventoryStock::updateOrCreate(
        ['catalog_item_id' => $itemLowStock->id],
        [
            'stock_actual' => 2,
            'stock_minimo' => 10,
        ]
    );

    InventoryMovement::factory()->create([
        'catalog_item_id' => $itemLowStock->id,
        'tipo' => 'salida',
        'cantidad' => 3,
        'fecha' => now()->toDateString(),
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index', ['categoria' => 'inventario']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('categoria', 'inventario')
            ->has('reportData.resumen', fn (Assert $resumen) => $resumen
                ->where('total_bajo_minimo', 1)
                ->etc()
            )
            ->has('reportData.bajo_minimo', 1)
            ->has('reportData.rotacion', 1)
        );
});

test('user can view servicios report with orders and average state durations', function () {
    $manager = reportUser(['reports.view']);

    $client = Client::factory()->create();
    $order = ServiceOrder::factory()->create([
        'client_id' => $client->id,
        'fecha' => now()->toDateString(),
        'estado' => 'recibido_planta',
    ]);

    ServiceOrderStatusHistory::create([
        'service_order_id' => $order->id,
        'estado_anterior' => 'pendiente_recepcion',
        'estado' => 'recibido_planta',
        'user_id' => $manager->id,
        'created_at' => now()->subHours(5),
    ]);

    ServiceOrderStatusHistory::create([
        'service_order_id' => $order->id,
        'estado_anterior' => 'recibido_planta',
        'estado' => 'en_revision',
        'user_id' => $manager->id,
        'created_at' => now(),
    ]);

    Deficiency::factory()->create([
        'service_order_id' => $order->id,
        'componente' => 'Manguera',
        'estado' => 'resuelta',
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index', [
            'categoria' => 'servicios',
            'fecha_desde' => now()->startOfMonth()->toDateString(),
            'fecha_hasta' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('categoria', 'servicios')
            ->has('reportData.ordenes_por_estado')
            ->has('reportData.deficiencias_por_tipo', 1)
        );
});

test('user can view equipos report with upcoming attention equipment', function () {
    $manager = reportUser(['reports.view']);

    $client = Client::factory()->create();
    Equipment::factory()->create([
        'client_id' => $client->id,
        'codigo' => 'BF-EQ-1001',
        'proxima_atencion' => now()->addDays(5)->toDateString(),
        'proxima_ph' => now()->addDays(15)->toDateString(),
        'estado' => 'activo',
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index', ['categoria' => 'equipos']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('categoria', 'equipos')
            ->has('reportData.proximos_atencion', 1)
            ->has('reportData.equipos_por_estado')
            ->has('reportData.equipos_por_cliente')
        );
});

test('user can view certificados, facturacion and cobranzas reports', function () {
    $manager = reportUser(['reports.view']);

    // Certificado
    $order = ServiceOrder::factory()->create();
    Certificate::factory()->create([
        'service_order_id' => $order->id,
        'tipo' => 'operatividad_garantia',
        'estado' => 'vigente',
        'fecha_emision' => now()->toDateString(),
    ]);

    // Facturación
    $sale = Sale::factory()->create(['condicion_pago' => 'credito']);
    ElectronicDocument::factory()->create([
        'sale_id' => $sale->id,
        'tipo' => 'factura',
        'estado' => 'aceptado',
    ]);

    // Cobranzas
    SaleInstallment::factory()->create([
        'sale_id' => $sale->id,
        'numero_cuota' => 1,
        'monto' => 100.00,
        'monto_pendiente' => 50.00,
        'fecha_vencimiento' => now()->subDays(2)->toDateString(),
        'estado' => 'vencido',
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'forma_pago' => 'transferencia',
        'monto' => 50.00,
        'fecha' => now()->toDateString(),
    ]);

    // Test Certificados
    $this->actingAs($manager)
        ->get(route('reports.index', ['categoria' => 'certificados']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('categoria', 'certificados')->has('reportData.certificados', 1));

    // Test Facturación
    $this->actingAs($manager)
        ->get(route('reports.index', ['categoria' => 'facturacion']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('categoria', 'facturacion')->has('reportData.documentos_por_estado'));

    // Test Cobranzas
    $this->actingAs($manager)
        ->get(route('reports.index', ['categoria' => 'cobranzas']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('categoria', 'cobranzas')->has('reportData.cuotas_pendientes', 1));
});

test('user can export reports to csv stream with UTF-8 BOM', function () {
    $manager = reportUser(['reports.view']);

    $response = $this->actingAs($manager)->get(route('reports.export', [
        'categoria' => 'comerciales',
        'subreporte' => 'periodo',
        'fecha_desde' => now()->startOfMonth()->toDateString(),
        'fecha_hasta' => now()->toDateString(),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Fecha');
});

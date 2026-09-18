<?php

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SalePayment;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->travelTo(Carbon::parse('2026-09-18 12:00:00'));
    $this->withoutVite();
});

function collectionUser(array $permissions = ['collections.view', 'collections.register_payment']): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function collectionInstallment(array $attributes = []): SaleInstallment
{
    return SaleInstallment::factory()->for(Sale::factory()->state([
        'condicion_pago' => 'credito', 'fecha' => '2026-09-01', 'total' => '100.00',
    ]))->create([
        'numero_cuota' => 1, 'monto' => '100.00', 'monto_pendiente' => '100.00',
        'fecha_vencimiento' => '2026-09-20', ...$attributes,
    ]);
}

/** @return array{monto: string, forma_pago: string, referencia: string, fecha: string, observaciones: string, idempotency_key: string} */
function collectionPaymentPayload(string $amount = '30.25'): array
{
    return [
        'monto' => $amount, 'forma_pago' => 'transferencia', 'referencia' => 'OP-12345',
        'fecha' => '2026-09-18', 'observaciones' => 'Abono recibido.',
        'idempotency_key' => (string) Str::uuid(),
    ];
}

function collectionPaymentRoute(SaleInstallment $installment): string
{
    return route('collections.payments.store', ['sale' => $installment->sale_id, 'installment' => $installment->id]);
}

test('guests cannot consult or register collections', function () {
    $installment = collectionInstallment();

    $this->get(route('collections.index'))->assertRedirect(route('login'));
    $this->get(route('collections.show', $installment->sale_id))->assertRedirect(route('login'));
    $this->post(collectionPaymentRoute($installment), collectionPaymentPayload())->assertRedirect(route('login'));

    $this->assertDatabaseCount('sale_payments', 0);
});

test('a partial payment reduces only its installment and records its details', function () {
    $user = collectionUser();
    $installment = collectionInstallment();
    $otherInstallment = SaleInstallment::factory()->for($installment->sale)->create([
        'numero_cuota' => 2, 'monto' => '100.00', 'monto_pendiente' => '100.00',
    ]);

    $this->actingAs($user)->post(collectionPaymentRoute($installment), [
        ...collectionPaymentPayload(), 'sale_id' => 999, 'user_id' => 999, 'estado' => 'pagado',
    ])->assertRedirect(route('collections.show', $installment->sale_id));

    $this->assertDatabaseHas('sale_installments', [
        'id' => $installment->id, 'monto_pendiente' => '69.75', 'estado' => 'pagado_parcial',
    ]);
    $this->assertDatabaseHas('sale_installments', ['id' => $otherInstallment->id, 'monto_pendiente' => '100.00', 'estado' => 'pendiente']);
    $this->assertDatabaseHas('sale_payments', [
        'sale_id' => $installment->sale_id, 'sale_installment_id' => $installment->id,
        'monto' => '30.25', 'forma_pago' => 'transferencia', 'referencia' => 'OP-12345',
        'fecha' => '2026-09-18', 'observaciones' => 'Abono recibido.', 'user_id' => $user->id,
    ]);
    $this->assertDatabaseCount('sale_payments', 1);
    $this->get(route('collections.show', $installment->sale_id))
        ->assertInertia(fn (Assert $page) => $page->component('collections/show')
            ->where('balance', '169.75')->where('installments.0.estado', 'parcial')
            ->where('payments.data.0.sale_installment_id', $installment->id));
});

test('a payment equal to the remaining balance settles the installment and removes the document from pending', function () {
    $installment = collectionInstallment(['monto_pendiente' => '69.75', 'estado' => 'pagado_parcial']);

    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), collectionPaymentPayload('69.75'))
        ->assertRedirect(route('collections.show', $installment->sale_id));

    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '0.00', 'estado' => 'pagado']);
    $this->assertDatabaseHas('sale_payments', ['sale_installment_id' => $installment->id, 'monto' => '69.75']);
    $this->get(route('collections.index'))->assertInertia(fn (Assert $page) => $page
        ->has('sales.data', 0)->where('summary.total_por_cobrar', '0.00')->where('summary.cobrado_este_mes', '69.75'));
    $this->get(route('collections.show', $installment->sale_id))->assertInertia(fn (Assert $page) => $page
        ->where('installments.0.estado', 'pagada')->where('balance', '0.00'));
});

test('cent payments do not accumulate floating point balance errors', function () {
    $installment = collectionInstallment(['monto' => '0.30', 'monto_pendiente' => '0.30']);
    $this->actingAs(collectionUser());

    $this->post(collectionPaymentRoute($installment), collectionPaymentPayload('0.10'))->assertRedirect();
    $this->post(collectionPaymentRoute($installment), collectionPaymentPayload('0.20'))->assertRedirect();

    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '0.00', 'estado' => 'pagado']);
    $this->assertDatabaseCount('sale_payments', 2);
});

test('overpayment and payments on settled installments leave the balance unchanged', function (string $balance, string $amount) {
    $installment = collectionInstallment(['monto_pendiente' => $balance]);

    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), collectionPaymentPayload($amount))
        ->assertInvalid(['monto' => 'El pago no puede superar el saldo pendiente de la cuota.']);

    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => $balance]);
    $this->assertDatabaseCount('sale_payments', 0);
})->with([['100.00', '100.01'], ['0.00', '0.01']]);

test('an identical payment retry is processed only once', function () {
    $installment = collectionInstallment();
    $payload = collectionPaymentPayload('100.00');
    $this->actingAs(collectionUser());

    $this->post(collectionPaymentRoute($installment), $payload)->assertRedirect();
    $this->post(collectionPaymentRoute($installment), $payload)->assertRedirect();

    $this->assertDatabaseCount('sale_payments', 1);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '0.00', 'estado' => 'pagado']);
});

test('a payment key cannot be reused with different payment details', function () {
    $installment = collectionInstallment();
    $payload = collectionPaymentPayload('10.00');
    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), $payload)->assertRedirect();

    $this->post(collectionPaymentRoute($installment), [...$payload, 'monto' => '20.00'])
        ->assertInvalid(['monto' => 'Esta solicitud ya registró otro pago. Recarga la página.']);

    $this->assertDatabaseCount('sale_payments', 1);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '90.00']);
});

test('payments reject invalid input without changing balances', function (string $field, mixed $value) {
    $installment = collectionInstallment();

    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), [...collectionPaymentPayload(), $field => $value])
        ->assertInvalid([$field]);

    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '100.00']);
    $this->assertDatabaseCount('sale_payments', 0);
})->with([
    'zero amount' => ['monto', '0'],
    'negative amount' => ['monto', '-1'],
    'fraction of a cent' => ['monto', '1.001'],
    'scientific notation' => ['monto', '1e1'],
    'too large' => ['monto', '10000000000.00'],
    'missing amount' => ['monto', null],
    'invalid method' => ['forma_pago', 'bitcoin'],
    'future date' => ['fecha', '2026-09-19'],
    'date before sale' => ['fecha', '2026-08-31'],
    'invalid date' => ['fecha', '2026-02-30'],
    'missing date' => ['fecha', null],
    'long reference' => ['referencia', str_repeat('x', 256)],
    'long note' => ['observaciones', str_repeat('x', 5001)],
    'invalid retry key' => ['idempotency_key', 'invalid'],
]);

test('nested payment routes reject an installment from a different sale', function () {
    $installment = collectionInstallment();
    $otherSale = Sale::factory()->create(['condicion_pago' => 'credito']);

    $this->actingAs(collectionUser())->post(route('collections.payments.store', [
        'sale' => $otherSale->id, 'installment' => $installment->id,
    ]), collectionPaymentPayload())->assertNotFound();

    $this->assertDatabaseCount('sale_payments', 0);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '100.00']);
});

test('cancelled and cash sales cannot receive installment payments', function (string $condition, string $status) {
    $installment = collectionInstallment();
    $installment->sale->update(['condicion_pago' => $condition, 'estado' => $status]);

    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), collectionPaymentPayload())
        ->assertInvalid(['monto' => 'Solo se pueden cobrar cuotas de ventas a crédito vigentes.']);

    $this->assertDatabaseCount('sale_payments', 0);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '100.00']);
})->with([['credito', 'anulada'], ['contado', 'completada']]);

test('a failure saving the payment rolls back the installment balance', function () {
    $installment = collectionInstallment();
    Event::listen('eloquent.creating: '.SalePayment::class, function (): void {
        throw new RuntimeException('Payment storage failed.');
    });

    $this->actingAs(collectionUser())->post(collectionPaymentRoute($installment), collectionPaymentPayload())->assertServerError();

    $this->assertDatabaseCount('sale_payments', 0);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '100.00', 'estado' => 'pendiente']);
});

test('installment display status is derived from balance and date without persisting overdue flags', function (string $dueDate, string $balance, string $expected) {
    $installment = collectionInstallment(['fecha_vencimiento' => $dueDate, 'monto_pendiente' => $balance]);

    $this->actingAs(collectionUser())->get(route('collections.show', $installment->sale_id))
        ->assertInertia(fn (Assert $page) => $page->where('installments.0.estado', $expected));

    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'estado' => 'pendiente']);
})->with([
    'unpaid overdue' => ['2026-09-17', '100.00', 'vencida'],
    'partial overdue' => ['2026-09-17', '50.00', 'vencida'],
    'paid overdue date' => ['2026-09-17', '0.00', 'pagada'],
    'due today' => ['2026-09-18', '100.00', 'pendiente'],
    'partial due today' => ['2026-09-18', '50.00', 'parcial'],
]);

test('the dashboard uses pending balances and effective payment dates across active sales', function () {
    $sale = Sale::factory()->create(['condicion_pago' => 'credito', 'fecha' => '2026-08-01']);
    foreach ([
        ['2026-09-17', '100.00', '80.00'],
        ['2026-09-18', '200.00', '200.00'],
        ['2026-09-20', '300.00', '300.00'],
        ['2026-09-21', '400.00', '400.00'],
        ['2026-09-10', '50.00', '0.00'],
    ] as $index => [$due, $amount, $balance]) {
        SaleInstallment::factory()->for($sale)->create([
            'numero_cuota' => $index + 1, 'fecha_vencimiento' => $due, 'monto' => $amount, 'monto_pendiente' => $balance,
        ]);
    }
    $cashSale = Sale::factory()->create(['condicion_pago' => 'contado']);
    $cancelledSale = Sale::factory()->create(['condicion_pago' => 'credito', 'estado' => 'anulada']);
    SaleInstallment::factory()->for($cashSale)->create(['monto_pendiente' => '900.00']);
    SaleInstallment::factory()->for($cancelledSale)->create(['monto_pendiente' => '900.00']);
    SalePayment::factory()->for($cashSale)->create(['monto' => '150.00', 'created_at' => '2026-09-01 00:00:00']);
    SalePayment::factory()->for($sale)->create(['monto' => '20.00', 'fecha' => '2026-09-10']);
    SalePayment::factory()->for($sale)->create(['monto' => '50.00', 'fecha' => '2026-09-18']);
    SalePayment::factory()->for($sale)->create(['monto' => '10.00', 'fecha' => '2026-08-31']);
    SalePayment::factory()->for($sale)->create(['monto' => '200.00', 'created_at' => '2026-08-31 23:59:59']);
    SalePayment::factory()->for($cancelledSale)->create(['monto' => '700.00']);
    SalePayment::factory()->for($sale)->create(['monto' => '800.00', 'fecha' => '2026-09-19']);

    $this->actingAs(collectionUser())->get(route('collections.index'))
        ->assertInertia(fn (Assert $page) => $page->component('collections/index')
            ->where('summary', ['total_por_cobrar' => '980.00', 'vencido' => '80.00', 'vence_esta_semana' => '500.00', 'cobrado_este_mes' => '220.00'])
            ->has('sales.data', 1)->where('sales.data.0.id', $sale->id)
            ->where('sales.data.0.saldo', '980.00')->where('sales.data.0.vencimiento', '2026-09-17')
            ->where('sales.data.0.cuotas', 5)->where('sales.data.0.cuotas_pendientes', 4)
            ->where('sales.data.0.estado', 'vencida'));
});

test('document search and computed status filters are combined with pagination', function () {
    $client = Client::factory()->create(['razon_social' => 'Cliente cobranza filtros']);
    for ($i = 0; $i < 16; $i++) {
        $sale = Sale::factory()->for($client)->create(['condicion_pago' => 'credito']);
        SaleInstallment::factory()->for($sale)->create(['monto' => '100.00', 'monto_pendiente' => '50.00', 'fecha_vencimiento' => '2026-09-20']);
    }
    $pending = Sale::factory()->for($client)->create(['condicion_pago' => 'credito']);
    SaleInstallment::factory()->for($pending)->create(['monto' => '100.00', 'monto_pendiente' => '100.00', 'fecha_vencimiento' => '2026-09-20']);
    collectionInstallment(['monto_pendiente' => '50.00']);
    $filters = ['search' => 'Cliente cobranza filtros', 'estado' => 'parcial'];

    $this->actingAs(collectionUser())->get(route('collections.index', $filters))
        ->assertInertia(fn (Assert $page) => $page->has('sales.data', 15)->where('sales.total', 16)
            ->where('sales.data.0.estado', 'parcial')
            ->where('sales.next_page_url', fn (string $url): bool => str_contains($url, 'estado=parcial') && str_contains($url, 'search=')));

    $this->get(route('collections.index', [...$filters, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('sales.data', 1)->where('sales.current_page', 2));
});

test('pending and overdue document filters use dates rather than stored overdue flags', function (string $status, string $due) {
    $match = collectionInstallment(['fecha_vencimiento' => $due]);
    collectionInstallment(['fecha_vencimiento' => $due === '2026-09-17' ? '2026-09-20' : '2026-09-17']);

    $this->actingAs(collectionUser())->get(route('collections.index', ['estado' => $status]))
        ->assertInertia(fn (Assert $page) => $page->has('sales.data', 1)->where('sales.data.0.id', $match->sale_id));
})->with([['pendiente', '2026-09-20'], ['vencida', '2026-09-17']]);

test('seeded roles retain their collection permissions', function (string $role, bool $canView, bool $canRegister) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $installment = collectionInstallment();

    expect($user->can('viewAny', SaleInstallment::class))->toBe($canView);
    expect($user->can('registerPayment', $installment))->toBe($canRegister);
})->with([
    ['Vendedor', true, false], ['Gerente', true, true], ['Técnico de Planta', false, false],
    ['Técnico de Campo', false, false], ['Almacén', false, false],
]);

test('viewers can consult but cannot register payments without the specific permission', function () {
    $installment = collectionInstallment();
    $viewer = collectionUser(['collections.view']);

    $this->actingAs($viewer)->get(route('collections.index'))->assertOk();
    $this->get(route('collections.show', $installment->sale_id))
        ->assertInertia(fn (Assert $page) => $page->where('canRegisterPayment', false));
    $this->post(collectionPaymentRoute($installment), collectionPaymentPayload())->assertForbidden();

    $this->assertDatabaseCount('sale_payments', 0);
    $this->assertDatabaseHas('sale_installments', ['id' => $installment->id, 'monto_pendiente' => '100.00']);
});

test('payment permission alone does not bypass view permission', function () {
    $installment = collectionInstallment();

    $this->actingAs(collectionUser(['collections.register_payment']))->get(route('collections.index'))->assertForbidden();
    $this->get(route('collections.show', $installment->sale_id))->assertForbidden();
    $this->post(collectionPaymentRoute($installment), collectionPaymentPayload())->assertForbidden();

    $this->assertDatabaseCount('sale_payments', 0);
});

test('creating a cash sale does not create pending installments', function () {
    $user = collectionUser(['collections.view', 'sales.view', 'sales.create']);
    $client = Client::factory()->create();
    $item = CatalogItem::factory()->create(['controla_stock' => false]);

    $this->actingAs($user)->post(route('sales.store'), [
        'client_id' => $client->id, 'tipo_comprobante' => 'boleta', 'fecha' => '2026-09-18', 'condicion_pago' => 'contado',
        'items' => [['catalog_item_id' => $item->id, 'cantidad' => 1, 'precio_unitario' => 100, 'descuento' => 0]],
        'payments' => [['forma_pago' => 'efectivo', 'monto' => 118]],
    ])->assertRedirect();

    $this->assertDatabaseCount('sale_installments', 0);
    $this->assertDatabaseHas('sale_payments', ['monto' => '118.00', 'sale_installment_id' => null]);
    $this->get(route('collections.index'))->assertInertia(fn (Assert $page) => $page
        ->has('sales.data', 0)->where('summary.cobrado_este_mes', '118.00'));
});

<?php

use App\Jobs\SendCreditDebitNoteJob;
use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use App\Models\User;
use App\Services\Billing\CreditDebitNoteService;
use App\Services\Billing\Data\SunatSendResult;
use App\Services\Billing\GreenterService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function billingNoteUser(array $permissions = ['billing.view', 'billing.credit_note']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('credit debit note route redirects guests to login', function () {
    $cpe = ElectronicDocument::factory()->aceptado()->create();

    $this->post(route('billing.credit-debit-notes.store', $cpe->id), [])
        ->assertRedirect(route('login'));
});

test('user without billing.credit_note permission is forbidden', function () {
    $user = User::factory()->create();
    $cpe = ElectronicDocument::factory()->aceptado()->create();

    $this->actingAs($user)
        ->post(route('billing.credit-debit-notes.store', $cpe->id), [
            'tipo' => 'nota_credito',
            'motivo' => '06',
            'detalle' => 'Devolucion total',
            'importe' => 100,
            'fecha' => now()->toDateString(),
        ])
        ->assertForbidden();
});

test('issuing a credit note references the original cpe and queues the send job', function () {
    Queue::fake();
    $user = billingNoteUser();
    $cpe = ElectronicDocument::factory()->aceptado()->create(['tipo' => 'factura']);

    $this->actingAs($user)
        ->post(route('billing.credit-debit-notes.store', $cpe->id), [
            'tipo' => 'nota_credito',
            'motivo' => '06',
            'detalle' => 'Devolucion total del producto',
            'importe' => 236,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('credit_debit_notes', [
        'cpe_afectado_id' => $cpe->id,
        'tipo' => 'nota_credito',
        'motivo' => '06',
        'serie' => 'FC01',
        'correlativo' => '00000001',
        'estado' => 'pendiente',
    ]);

    $note = CreditDebitNote::firstOrFail();
    expect($note->cpeAfectado->id)->toBe($cpe->id);

    Queue::assertPushed(SendCreditDebitNoteJob::class, fn ($job) => $job->note->id === $note->id);
});

test('issuing a debit note uses the debit note series', function () {
    Queue::fake();
    $user = billingNoteUser();
    $cpe = ElectronicDocument::factory()->aceptado()->create(['tipo' => 'boleta']);

    $this->actingAs($user)
        ->post(route('billing.credit-debit-notes.store', $cpe->id), [
            'tipo' => 'nota_debito',
            'motivo' => '01',
            'detalle' => 'Intereses por mora',
            'importe' => 50,
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('credit_debit_notes', [
        'cpe_afectado_id' => $cpe->id,
        'tipo' => 'nota_debito',
        'serie' => 'BD01',
    ]);
});

test('cannot issue a note when the cpe is not aceptado', function () {
    Queue::fake();
    $user = billingNoteUser();
    $cpe = ElectronicDocument::factory()->create(['estado' => 'pendiente']);

    $this->actingAs($user)
        ->post(route('billing.credit-debit-notes.store', $cpe->id), [
            'tipo' => 'nota_credito',
            'motivo' => '06',
            'detalle' => 'Devolucion total',
            'importe' => 100,
            'fecha' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('cpe_afectado_id');

    $this->assertDatabaseCount('credit_debit_notes', 0);
    Queue::assertNothingPushed();
});

test('motivo must be valid for the chosen tipo', function () {
    $user = billingNoteUser();
    $cpe = ElectronicDocument::factory()->aceptado()->create();

    $this->actingAs($user)
        ->post(route('billing.credit-debit-notes.store', $cpe->id), [
            'tipo' => 'nota_credito',
            'motivo' => '99',
            'detalle' => 'Motivo invalido',
            'importe' => 100,
            'fecha' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('motivo');
});

test('sending a note that sunat accepts stores xml/cdr and marks it aceptado', function () {
    Storage::fake('local');

    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendNote')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Note>signed</Note>',
            cdrZip: 'binary-zip-content',
            code: '0',
            description: 'La Nota de Credito ha sido aceptada',
        ));
    });

    $note = CreditDebitNote::factory()->create(['estado' => 'pendiente', 'intentos' => 0]);

    app(CreditDebitNoteService::class)->send($note);

    $note->refresh();

    expect($note->estado)->toBe('aceptado');
    expect($note->intentos)->toBe(1);
    expect($note->respuesta_sunat)->toContain('aceptada');
    expect($note->hash)->not->toBeNull();

    Storage::disk('local')->assertExists($note->xml_path);
    Storage::disk('local')->assertExists($note->cdr_path);
});

test('sending a note that fails marks it as error', function () {
    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendNote')->once()->andReturn(
            SunatSendResult::failed('No se pudo conectar con el servicio de SUNAT.')
        );
    });

    $note = CreditDebitNote::factory()->create(['estado' => 'pendiente', 'intentos' => 0]);

    app(CreditDebitNoteService::class)->send($note);

    expect($note->fresh()->estado)->toBe('error');
    expect($note->fresh()->error)->toContain('SUNAT');
});

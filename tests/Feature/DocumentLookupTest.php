<?php

use App\Models\User;
use App\Services\DocumentLookupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Config::set('services.apisperu.base_url', 'https://dniruc.apisperu.com/api/v1');
    Config::set('services.apisperu.token', 'test-token');
});

function clientLookupUser(): User
{
    Permission::findOrCreate('clients.create', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('clients.create');

    return $user;
}

test('DocumentLookupService successfully normalizes DNI response', function () {
    Http::fake([
        'https://dniruc.apisperu.com/api/v1/dni/12345678' => Http::response([
            'dni' => '12345678',
            'nombres' => 'JUAN CARLOS',
            'apellidoPaterno' => 'PEREZ',
            'apellidoMaterno' => 'GARCIA',
            'codVerifica' => '1',
        ], 200),
    ]);

    $service = app(DocumentLookupService::class);
    $result = $service->lookupDni('12345678');

    expect($result)->toBe([
        'razon_social' => 'JUAN CARLOS PEREZ GARCIA',
    ]);
});

test('DocumentLookupService successfully normalizes RUC response', function () {
    Http::fake([
        'https://dniruc.apisperu.com/api/v1/ruc/20123456789' => Http::response([
            'ruc' => '20123456789',
            'razonSocial' => 'ACME SEGURIDAD S.A.C.',
            'nombreComercial' => 'ACME FIRE',
            'telefonos' => ['987654321'],
            'estado' => 'ACTIVO',
            'condicion' => 'HABIDO',
            'direccion' => 'AV. LOS HEROES 123',
            'departamento' => 'LIMA',
            'provincia' => 'LIMA',
            'distrito' => 'MIRAFLORES',
            'ubigeo' => '150122',
            'capital' => 'LIMA',
        ], 200),
    ]);

    $service = app(DocumentLookupService::class);
    $result = $service->lookupRuc('20123456789');

    expect($result)->toBe([
        'razon_social' => 'ACME SEGURIDAD S.A.C.',
        'nombre_comercial' => 'ACME FIRE',
        'direccion_fiscal' => 'AV. LOS HEROES 123',
        'departamento' => 'LIMA',
        'provincia' => 'LIMA',
        'distrito' => 'MIRAFLORES',
        'ubigeo' => '150122',
    ]);
});

test('DocumentLookupService returns null on HTTP error or failure without throwing exceptions', function () {
    Http::fake([
        'https://dniruc.apisperu.com/api/v1/dni/00000000' => Http::response(['message' => 'No encontrado'], 404),
        'https://dniruc.apisperu.com/api/v1/ruc/00000000000' => Http::response(['message' => 'Error de servidor'], 500),
    ]);

    $service = app(DocumentLookupService::class);

    expect($service->lookupDni('00000000'))->toBeNull();
    expect($service->lookupRuc('00000000000'))->toBeNull();
});

test('guests cannot access document lookup endpoint', function () {
    $this->getJson(route('clients.document-lookup', ['tipo_documento' => 'dni', 'numero' => '12345678']))
        ->assertUnauthorized();
});

test('user without clients.create cannot access document lookup endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'dni', 'numero' => '12345678']))
        ->assertForbidden();
});

test('document lookup endpoint validates required format for dni and ruc', function () {
    $user = clientLookupUser();

    // Invalid DNI length (not 8 digits)
    $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'dni', 'numero' => '1234']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['numero']);

    // Invalid RUC length (not 11 digits)
    $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'ruc', 'numero' => '123456789']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['numero']);
});

test('document lookup endpoint returns null data for pasaporte or ce without external call', function () {
    Http::fake();

    $user = clientLookupUser();

    $response = $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'pasaporte', 'numero' => 'A12345678']))
        ->assertOk();

    expect($response->json())->toBe(['data' => null]);
    Http::assertNothingSent();
});

test('document lookup endpoint returns json data for valid dni query', function () {
    Http::fake([
        'https://dniruc.apisperu.com/api/v1/dni/72123456' => Http::response([
            'dni' => '72123456',
            'nombres' => 'MARIA ELENA',
            'apellidoPaterno' => 'RODRIGUEZ',
            'apellidoMaterno' => 'LOPEZ',
        ], 200),
    ]);

    $user = clientLookupUser();

    $response = $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'dni', 'numero' => '72123456']))
        ->assertOk();

    expect($response->json('data'))->toBe([
        'razon_social' => 'MARIA ELENA RODRIGUEZ LOPEZ',
    ]);
});

test('document lookup endpoint returns json data for valid ruc query', function () {
    Http::fake([
        'https://dniruc.apisperu.com/api/v1/ruc/20501234567' => Http::response([
            'ruc' => '20501234567',
            'razonSocial' => 'SERVICIOS GENERALES DEL SUR S.A.C.',
            'nombreComercial' => 'SERVISUR',
            'direccion' => 'CALLE LAS PALMAS 456',
            'departamento' => 'AREQUIPA',
            'provincia' => 'AREQUIPA',
            'distrito' => 'YANAHUARA',
            'ubigeo' => '040126',
        ], 200),
    ]);

    $user = clientLookupUser();

    $response = $this->actingAs($user)
        ->getJson(route('clients.document-lookup', ['tipo_documento' => 'ruc', 'numero' => '20501234567']))
        ->assertOk();

    expect($response->json('data'))->toBe([
        'razon_social' => 'SERVICIOS GENERALES DEL SUR S.A.C.',
        'nombre_comercial' => 'SERVISUR',
        'direccion_fiscal' => 'CALLE LAS PALMAS 456',
        'departamento' => 'AREQUIPA',
        'provincia' => 'AREQUIPA',
        'distrito' => 'YANAHUARA',
        'ubigeo' => '040126',
    ]);
});

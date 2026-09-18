<?php

use App\Models\Deficiency;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\DeficiencyAuthorizedNotification;
use App\Notifications\DeficiencyPendingAuthorizationNotification;
use App\Notifications\ServiceOrderAssignedNotification;
use App\Notifications\ServiceOrderFinishedNotification;
use App\Notifications\SunatErrorNotification;
use App\Services\Billing\Data\SunatSendResult;
use App\Services\Billing\SunatResponseService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeUserWithPermission(string ...$permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

// ── Guards ────────────────────────────────────────────────────────────────────

test('notification routes require authentication', function () {
    $this->getJson(route('notifications.index'))->assertUnauthorized();
    $this->getJson(route('notifications.unread-count'))->assertUnauthorized();
});

// ── ServiceOrderAssignedNotification ─────────────────────────────────────────

test('creating a service order notifies the assigned technician', function () {
    Notification::fake();

    $tecnico = User::factory()->create();
    $order = ServiceOrder::factory()->create(['tecnico_user_id' => $tecnico->id]);

    Notification::assertSentTo($tecnico, ServiceOrderAssignedNotification::class, function ($notif) use ($order) {
        return $notif->serviceOrder->id === $order->id;
    });
});

test('creating a service order without a technician sends no assignment notification', function () {
    Notification::fake();

    ServiceOrder::factory()->create(['tecnico_user_id' => null]);

    Notification::assertNothingSent();
});

// ── ServiceOrderFinishedNotification ─────────────────────────────────────────

test('transitioning service order to trabajo_terminado notifies the technician', function () {
    Notification::fake();

    $tecnico = User::factory()->create();
    $actor = makeUserWithPermission('service_orders.view', 'service_orders.execute');
    $order = ServiceOrder::factory()->create([
        'estado' => 'en_proceso',
        'tecnico_user_id' => $tecnico->id,
    ]);

    $order->transitionTo('trabajo_terminado', $actor);

    Notification::assertSentTo($tecnico, ServiceOrderFinishedNotification::class, function ($notif) use ($order) {
        return $notif->serviceOrder->id === $order->id;
    });
});

// ── DeficiencyPendingAuthorizationNotification ────────────────────────────────

test('deficiency transition to esperando_autorizacion notifies authorizers', function () {
    Notification::fake();

    $authorizer = makeUserWithPermission('deficiencies.view', 'deficiencies.create', 'deficiencies.authorize');
    $actor = makeUserWithPermission('deficiencies.view', 'deficiencies.create');
    $deficiency = Deficiency::factory()->create(['estado' => 'detectada']);

    $deficiency->transitionTo('esperando_autorizacion', $actor);

    Notification::assertSentTo($authorizer, DeficiencyPendingAuthorizationNotification::class, function ($notif) use ($deficiency) {
        return $notif->deficiency->id === $deficiency->id;
    });
});

// ── DeficiencyAuthorizedNotification ─────────────────────────────────────────

test('deficiency transition to autorizada notifies order technician', function () {
    Notification::fake();

    $tecnico = User::factory()->create();
    $order = ServiceOrder::factory()->create(['tecnico_user_id' => $tecnico->id]);
    $deficiency = Deficiency::factory()->create([
        'service_order_id' => $order->id,
        'estado' => 'esperando_autorizacion',
    ]);
    $authorizer = makeUserWithPermission('deficiencies.view', 'deficiencies.authorize');

    $this->actingAs($authorizer)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'autorizada'])
        ->assertRedirect();

    Notification::assertSentTo($tecnico, DeficiencyAuthorizedNotification::class);
});

test('autorizada notification is not sent when actor is the technician', function () {
    Notification::fake();

    $tecnico = makeUserWithPermission('deficiencies.view', 'deficiencies.authorize');
    $order = ServiceOrder::factory()->create(['tecnico_user_id' => $tecnico->id]);
    $deficiency = Deficiency::factory()->create([
        'service_order_id' => $order->id,
        'estado' => 'esperando_autorizacion',
    ]);

    $this->actingAs($tecnico)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'autorizada'])
        ->assertRedirect();

    Notification::assertNotSentTo($tecnico, DeficiencyAuthorizedNotification::class);
});

// ── SunatErrorNotification ───────────────────────────────────────────────────

test('SUNAT send failure notifies the seller', function () {
    Notification::fake();

    $vendedor = User::factory()->create();
    $sale = Sale::factory()->create(['vendedor_user_id' => $vendedor->id]);
    $document = ElectronicDocument::factory()->create(['sale_id' => $sale->id, 'tipo' => 'boleta', 'serie' => 'B001', 'correlativo' => '00000001', 'intentos' => 0]);

    $failedResult = SunatSendResult::failed('Certificado no valido');

    app(SunatResponseService::class)->apply($document, $failedResult);

    Notification::assertSentTo($vendedor, SunatErrorNotification::class);
});

// ── Listing & read endpoints ──────────────────────────────────────────────────

test('user can list their own notifications', function () {
    $user = User::factory()->create();
    $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => ServiceOrderAssignedNotification::class,
        'data' => ['type' => 'service_order_assigned', 'title' => 'Test', 'message' => 'msg'],
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

test('user cannot see another users notifications', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $owner->notifications()->create([
        'id' => Str::uuid(),
        'type' => ServiceOrderAssignedNotification::class,
        'data' => ['type' => 'service_order_assigned', 'title' => 'Private', 'message' => 'private'],
        'read_at' => null,
    ]);

    $response = $this->actingAs($other)
        ->getJson(route('notifications.index'))
        ->assertOk();

    expect($response->json('total'))->toBe(0);
});

test('user can mark a single notification as read', function () {
    $user = User::factory()->create();
    $notifId = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $notifId,
        'type' => ServiceOrderAssignedNotification::class,
        'data' => ['type' => 'service_order_assigned', 'title' => 'Test', 'message' => 'msg'],
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->patchJson(route('notifications.read', $notifId))
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(DatabaseNotification::find($notifId)->read_at)->not->toBeNull();
});

test('user cannot mark another users notification as read', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $notifId = (string) Str::uuid();

    $owner->notifications()->create([
        'id' => $notifId,
        'type' => ServiceOrderAssignedNotification::class,
        'data' => ['type' => 'service_order_assigned', 'title' => 'Test', 'message' => 'msg'],
        'read_at' => null,
    ]);

    $this->actingAs($other)
        ->patchJson(route('notifications.read', $notifId))
        ->assertNotFound();

    expect(DatabaseNotification::find($notifId)->read_at)->toBeNull();
});

test('mark all read sets read_at for all user notifications', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $_) {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ServiceOrderAssignedNotification::class,
            'data' => ['type' => 'service_order_assigned', 'title' => 'Test', 'message' => 'msg'],
            'read_at' => null,
        ]);
    }

    $this->actingAs($user)
        ->patchJson(route('notifications.read-all'))
        ->assertOk();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('unread count endpoint returns correct number', function () {
    $user = User::factory()->create();

    foreach (range(1, 2) as $_) {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ServiceOrderAssignedNotification::class,
            'data' => ['type' => 'service_order_assigned', 'title' => 'Test', 'message' => 'msg'],
            'read_at' => null,
        ]);
    }

    $this->actingAs($user)
        ->getJson(route('notifications.unread-count'))
        ->assertOk()
        ->assertJson(['count' => 2]);
});

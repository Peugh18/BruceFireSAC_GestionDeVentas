<?php

use App\Models\Sale;
use App\Models\User;
use App\Services\AI\GeminiAssistantService;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

function assistantUser(array $permissions = ['reports.view']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('ai assistant routes redirect guests to login', function () {
    $this->get(route('ai-assistant.index'))->assertRedirect(route('login'));
    $this->post(route('ai-assistant.ask'), ['question' => 'hola'])->assertRedirect(route('login'));
});

test('user without reports.view is forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('ai-assistant.index'))->assertForbidden();
    $this->actingAs($user)
        ->post(route('ai-assistant.ask'), ['question' => '¿Cuanto vendimos?'])
        ->assertForbidden();
});

test('asking a question sends a bounded context to gemini and returns the answer', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Vendimos S/ 500.00 este mes.']]]],
            ],
        ]),
    ]);

    Sale::factory()->create(['estado' => 'completada', 'fecha' => now()->toDateString(), 'total' => 500]);

    $user = assistantUser();

    $this->actingAs($user)
        ->post(route('ai-assistant.ask'), ['question' => 'Cuanto vendimos este mes?'])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('ai-assistant.index'))
        ->assertInertia(fn ($page) => $page
            ->component('ai-assistant/index')
            ->where('question', 'Cuanto vendimos este mes?')
            ->where('answer', 'Vendimos S/ 500.00 este mes.')
        );

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($request->url(), 'generativelanguage.googleapis.com')
            && str_contains($request->url(), 'gemini-3.8-flash')
            && str_contains($body, 'Ventas del mes')
            && str_contains($body, 'Cuanto vendimos este mes?');
    });
});

test('question is required', function () {
    $user = assistantUser();

    $this->actingAs($user)
        ->post(route('ai-assistant.ask'), ['question' => ''])
        ->assertSessionHasErrors('question');
});

test('service refuses to build context or call gemini for a user without reports.view', function () {
    Http::fake();

    $user = User::factory()->create();

    $answer = app(GeminiAssistantService::class)->ask($user, '¿Cuanto vendimos este mes?');

    expect($answer)->toContain('No tienes permiso');
    Http::assertNothingSent();
});

test('gemini failures degrade to a safe fallback message', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
    ]);

    $user = assistantUser();

    $answer = app(GeminiAssistantService::class)->ask($user, '¿Cuanto vendimos este mes?');

    expect($answer)->toContain('No se pudo conectar');
});

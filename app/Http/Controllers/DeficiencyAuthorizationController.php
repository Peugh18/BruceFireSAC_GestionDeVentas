<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeficiencyAuthorizationRequest;
use App\Models\Deficiency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeficiencyAuthorizationController extends Controller
{
    /**
     * Register the client's authorization for a deficiency's additional work and
     * transition the deficiency to "autorizada" using the existing state machine.
     */
    public function store(StoreDeficiencyAuthorizationRequest $request, Deficiency $deficiency): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($deficiency, $validated, $request): void {
            if ($deficiency->estado !== 'esperando_autorizacion') {
                throw ValidationException::withMessages([
                    'estado' => 'Solo se puede registrar la autorización de deficiencias en estado "Esperando autorización".',
                ]);
            }

            $deficiency->authorizations()->create([
                'quote_id' => $validated['quote_id'] ?? null,
                'autorizado_por' => $validated['autorizado_por'],
                'canal' => $validated['canal'],
                'fecha' => $validated['fecha'],
                'observacion' => $validated['observacion'] ?? null,
            ]);

            $deficiency->transitionTo('autorizada', $request->user());
        });

        return back()->with('status', 'Autorización del adicional registrada correctamente.');
    }
}

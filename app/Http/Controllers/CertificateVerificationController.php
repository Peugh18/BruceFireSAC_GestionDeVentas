<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Inertia\Inertia;
use Inertia\Response;

class CertificateVerificationController extends Controller
{
    /**
     * Public, unauthenticated verification page for a certificate by its unpredictable token.
     */
    public function show(string $token): Response
    {
        $certificate = Certificate::query()
            ->with(['serviceOrder.client', 'items.equipment'])
            ->where('token', $token)
            ->firstOrFail();

        return Inertia::render('certificates/verify', [
            'certificate' => [
                'numero' => $certificate->numero,
                'tipo' => Certificate::TIPO_LABELS[$certificate->tipo],
                'estado' => Certificate::ESTADO_LABELS[$certificate->estado],
                'estado_key' => $certificate->estado,
                'es_vigente' => $certificate->isVigente(),
                'fecha_emision' => $certificate->fecha_emision?->toDateString(),
                'fecha_vigencia' => $certificate->fecha_vigencia?->toDateString(),
                'cliente' => $certificate->serviceOrder->client->razon_social,
                'orden_codigo' => $certificate->serviceOrder->codigo,
                'equipos' => $certificate->items->map(fn ($item) => [
                    'codigo' => $item->equipment->codigo,
                    'tipo_equipo' => $item->equipment->tipo_equipo,
                    'capacidad' => $item->equipment->capacidad,
                ])->values(),
            ],
        ]);
    }
}

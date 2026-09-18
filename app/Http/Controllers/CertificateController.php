<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateStatusRequest;
use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Certificate::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $certificates = Certificate::query()
            ->with(['serviceOrder.client', 'items.equipment'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('serviceOrder', function ($q) use ($search) {
                        $q->where('codigo', 'like', "%{$search}%");
                    })
                    ->orWhereHas('serviceOrder.client', function ($q) use ($search) {
                        $q->where('razon_social', 'like', "%{$search}%");
                    });
            })
            ->when($estado !== '' && $estado !== 'todos', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('certificates/index', [
            'certificates' => $certificates,
            'tipoLabels' => Certificate::TIPO_LABELS,
            'estadoLabels' => Certificate::ESTADO_LABELS,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Store a newly issued certificate with its dynamic equipment items.
     */
    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $certificate = DB::transaction(function () use ($validated, $request) {
            $certificate = Certificate::create([
                'service_order_id' => $validated['service_order_id'],
                'tipo' => $validated['tipo'],
                'fecha_emision' => $validated['fecha_emision'],
                'fecha_vigencia' => $validated['fecha_vigencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'generado_por_user_id' => $request->user()->id,
            ]);

            foreach (array_unique($validated['equipment_ids']) as $equipmentId) {
                $certificate->items()->create(['equipment_id' => $equipmentId]);
            }

            return $certificate;
        });

        return to_route('certificates.show', $certificate->id)->with('status', 'Certificado generado correctamente.');
    }

    /**
     * Display a certificate with its dynamic equipment table and verification QR.
     */
    public function show(Certificate $certificate): Response
    {
        $this->authorize('view', $certificate);

        $certificate->load(['serviceOrder.client', 'serviceOrder.clientSite', 'items.equipment', 'generadoPorUser']);

        $verificationUrl = route('certificates.verify', $certificate->token);

        return Inertia::render('certificates/show', [
            'certificate' => $certificate,
            'tipoLabels' => Certificate::TIPO_LABELS,
            'estadoLabels' => Certificate::ESTADO_LABELS,
            'verificationUrl' => $verificationUrl,
            'qrSvg' => QrCode::size(220)->generate($verificationUrl),
        ]);
    }

    /**
     * Update the certificate status respecting the allowed state machine.
     */
    public function updateStatus(UpdateCertificateStatusRequest $request, Certificate $certificate): RedirectResponse
    {
        $certificate->transitionTo($request->validated('estado'));

        return back()->with('status', 'Estado del certificado actualizado correctamente.');
    }
}

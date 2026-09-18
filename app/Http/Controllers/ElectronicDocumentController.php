<?php

namespace App\Http\Controllers;

use App\Http\Requests\RetryElectronicDocumentRequest;
use App\Http\Requests\StoreElectronicDocumentRequest;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElectronicDocumentController extends Controller
{
    public function __construct(private readonly BillingService $billingService) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::in(array_keys(ElectronicDocument::ESTADO_LABELS))],
        ]);

        $documents = ElectronicDocument::query()
            ->with(['sale.client'])
            ->when($filters['desde'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['hasta'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (ElectronicDocument $document): array {
                return [
                    'id' => $document->id,
                    'sale_id' => $document->sale_id,
                    'tipo' => $document->tipo,
                    'serie' => $document->serie,
                    'correlativo' => $document->correlativo,
                    'estado' => $document->estado,
                    'respuesta_sunat' => $document->respuesta_sunat,
                    'error' => $document->error,
                    'intentos' => $document->intentos,
                    'fecha_envio' => $document->fecha_envio?->toDateTimeString(),
                    'created_at' => $document->created_at?->toDateTimeString(),
                    'xml_path' => $document->xml_path,
                    'cdr_path' => $document->cdr_path,
                    'has_xml' => $document->xml_path !== null && Storage::disk('local')->exists($document->xml_path),
                    'has_cdr' => $document->cdr_path !== null && Storage::disk('local')->exists($document->cdr_path),
                    'sale' => [
                        'id' => $document->sale?->id,
                        'numero' => $document->sale?->numero,
                        'fecha' => $document->sale?->fecha?->toDateString(),
                        'client' => [
                            'razon_social' => $document->sale?->client?->razon_social,
                            'numero_documento' => $document->sale?->client?->numero_documento,
                        ],
                    ],
                ];
            });

        return Inertia::render('billing/index', [
            'documents' => $documents,
            'filters' => [
                'desde' => $filters['desde'] ?? '',
                'hasta' => $filters['hasta'] ?? '',
                'estado' => $filters['estado'] ?? 'todos',
            ],
            'estadoLabels' => ElectronicDocument::ESTADO_LABELS,
            'hasDownloadRoutes' => false,
        ]);
    }

    /**
     * Issue the electronic document (factura/boleta) for a sale. The actual
     * submission to SUNAT is queued, not performed synchronously here.
     */
    public function store(StoreElectronicDocumentRequest $request, Sale $sale): RedirectResponse
    {
        $this->billingService->issue($sale);

        return back()->with('status', 'Comprobante electronico en cola de envio a SUNAT.');
    }

    /**
     * Re-queue the submission of a document that is currently in error.
     */
    public function retry(RetryElectronicDocumentRequest $request, ElectronicDocument $electronicDocument): RedirectResponse
    {
        $this->billingService->retry($electronicDocument);

        return back()->with('status', 'Reintento de envio a SUNAT en cola.');
    }

    public function downloadXml(ElectronicDocument $electronicDocument): StreamedResponse
    {
        abort_unless($electronicDocument->xml_path !== null && Storage::disk('local')->exists($electronicDocument->xml_path), 404);

        return Storage::disk('local')->download(
            $electronicDocument->xml_path,
            "{$electronicDocument->tipo}-{$electronicDocument->serie}-{$electronicDocument->correlativo}.xml"
        );
    }

    public function downloadCdr(ElectronicDocument $electronicDocument): StreamedResponse
    {
        abort_unless($electronicDocument->cdr_path !== null && Storage::disk('local')->exists($electronicDocument->cdr_path), 404);

        return Storage::disk('local')->download(
            $electronicDocument->cdr_path,
            "R-{$electronicDocument->tipo}-{$electronicDocument->serie}-{$electronicDocument->correlativo}.zip"
        );
    }
}

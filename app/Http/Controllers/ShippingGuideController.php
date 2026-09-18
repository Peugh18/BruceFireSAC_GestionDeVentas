<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShippingGuideRequest;
use App\Models\Client;
use App\Models\Sale;
use App\Models\ShippingGuide;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShippingGuideController extends Controller
{
    public function __construct(private readonly ShippingService $shippingService) {}

    /**
     * Display a listing of shipping guides (GRE).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ShippingGuide::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $guides = ShippingGuide::query()
            ->with(['sale', 'destinatarioClient', 'items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlativo', 'like', "%{$search}%")
                    ->orWhere('destinatario_nombre', 'like', "%{$search}%")
                    ->orWhereHas('destinatarioClient', function ($q) use ($search) {
                        $q->where('razon_social', 'like', "%{$search}%");
                    });
            })
            ->when($estado !== '' && $estado !== 'todos', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('shipping/index', [
            'guides' => $guides,
            'motivoLabels' => ShippingGuide::MOTIVO_LABELS,
            'modalidadLabels' => ShippingGuide::MODALIDAD_LABELS,
            'estadoLabels' => ShippingGuide::ESTADO_LABELS,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Show the form for creating a new shipping guide.
     */
    public function create(): Response
    {
        $this->authorize('create', ShippingGuide::class);

        $clients = Client::query()
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get(['id', 'codigo', 'razon_social', 'tipo_documento', 'numero_documento']);

        $sales = Sale::query()
            ->with('items.catalogItem:id,nombre,unidad')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'numero', 'client_id']);

        return Inertia::render('shipping/create', [
            'clients' => $clients,
            'sales' => $sales,
            'motivoLabels' => ShippingGuide::MOTIVO_LABELS,
            'modalidadLabels' => ShippingGuide::MODALIDAD_LABELS,
        ]);
    }

    /**
     * Store a newly created shipping guide and queue its submission to SUNAT.
     */
    public function store(StoreShippingGuideRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $guide = DB::transaction(function () use ($validated) {
            $serie = config('shipping.serie');

            $guide = ShippingGuide::create([
                'sale_id' => $validated['sale_id'] ?? null,
                'motivo_traslado' => $validated['motivo_traslado'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'origen' => $validated['origen'],
                'destino' => $validated['destino'],
                'destinatario_client_id' => $validated['destinatario_client_id'] ?? null,
                'destinatario_nombre' => $validated['destinatario_nombre'] ?? null,
                'destinatario_documento' => $validated['destinatario_documento'] ?? null,
                'peso_total' => $validated['peso_total'],
                'modalidad' => $validated['modalidad'],
                'transportista_razon_social' => $validated['transportista_razon_social'] ?? null,
                'transportista_ruc' => $validated['transportista_ruc'] ?? null,
                'vehiculo_placa' => $validated['vehiculo_placa'] ?? null,
                'conductor_nombre' => $validated['conductor_nombre'] ?? null,
                'conductor_licencia' => $validated['conductor_licencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'serie' => $serie,
                'correlativo' => ShippingGuide::nextCorrelativo($serie),
                'estado' => 'pendiente',
            ]);

            foreach ($validated['items'] as $item) {
                $guide->items()->create([
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'unidad' => $item['unidad'] ?? 'NIU',
                    'peso' => $item['peso'] ?? null,
                ]);
            }

            return $guide;
        });

        $this->shippingService->issue($guide);

        return to_route('shipping.index')->with('status', 'Guia de remision generada y en cola de envio a SUNAT.');
    }
}

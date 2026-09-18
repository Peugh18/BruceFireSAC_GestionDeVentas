<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePickupRequest;
use App\Http\Requests\UpdateCustodyStepRequest;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPickup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderPickupController extends Controller
{
    /**
     * Display a listing of pickups.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceOrderPickup::class);

        $search = $request->string('search')->toString();

        $pickups = ServiceOrderPickup::query()
            ->with([
                'serviceOrder:id,numero_orden,codigo',
                'client:id,razon_social,nombre_comercial,numero_documento',
                'site:id,nombre,direccion',
                'recogidoPorUser:id,name',
                'recibidoPlantaUser:id,name',
                'entregadoPorUser:id,name',
                'recibidoClientePorUser:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('serviceOrder', fn ($so) => $so->where('codigo', 'like', "%{$search}%")->orWhere('numero_orden', 'like', "%{$search}%"))
                        ->orWhereHas('client', fn ($c) => $c->where('razon_social', 'like', "%{$search}%")->orWhere('nombre_comercial', 'like', "%{$search}%"))
                        ->orWhere('contacto', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('pickups/index', [
            'pickups' => $pickups,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show form for creating a new pickup.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ServiceOrderPickup::class);

        $serviceOrderId = $request->integer('service_order_id');
        $selectedOrder = null;

        if ($serviceOrderId > 0) {
            $selectedOrder = ServiceOrder::with(['client', 'site'])
                ->find($serviceOrderId);
        }

        $serviceOrders = ServiceOrder::query()
            ->with(['client:id,razon_social', 'site:id,nombre'])
            ->whereNotIn('estado', ['cerrado', 'cancelado'])
            ->orderByDesc('id')
            ->get(['id', 'codigo', 'numero_orden', 'client_id', 'client_site_id']);

        return Inertia::render('pickups/create', [
            'serviceOrders' => $serviceOrders,
            'selectedOrder' => $selectedOrder,
        ]);
    }

    /**
     * Store a newly created pickup.
     */
    public function store(StorePickupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $serviceOrder = ServiceOrder::findOrFail($validated['service_order_id']);

        $pickup = ServiceOrderPickup::create([
            'service_order_id' => $serviceOrder->id,
            'client_id' => $serviceOrder->client_id,
            'client_site_id' => $validated['client_site_id'] ?? $serviceOrder->client_site_id,
            'contacto' => $validated['contacto'],
            'fecha_hora_recojo' => $validated['fecha_hora_recojo'],
            'cantidad' => $validated['cantidad'],
            'observaciones' => $validated['observaciones'] ?? null,
            'conforme_nombre' => $validated['conforme_nombre'] ?? null,
            'conforme_dni' => $validated['conforme_dni'] ?? null,
            'conforme_firma' => $validated['conforme_firma'] ?? null,
            'recogido_por_user_id' => $request->user()->id,
            'recogido_en' => now(),
        ]);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                if ($file->isValid()) {
                    $pickup->addMedia($file)->toMediaCollection('fotos');
                }
            }
        }

        return to_route('pickups.show', $pickup)->with('status', 'Recojo registrado correctamente.');
    }

    /**
     * Display the specified pickup record and custody timeline.
     */
    public function show(ServiceOrderPickup $pickup): Response
    {
        $this->authorize('view', $pickup);

        $pickup->load([
            'serviceOrder:id,numero_orden,codigo,estado',
            'client:id,razon_social,nombre_comercial,numero_documento',
            'site:id,nombre,direccion',
            'recogidoPorUser:id,name',
            'recibidoPlantaUser:id,name',
            'entregadoPorUser:id,name',
            'recibidoClientePorUser:id,name',
        ]);

        $fotos = $pickup->getMedia('fotos')->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'file_name' => $media->file_name,
            'size' => $media->human_readable_size,
        ]);

        return Inertia::render('pickups/show', [
            'pickup' => $pickup,
            'fotos' => $fotos,
        ]);
    }

    /**
     * Advance custody step.
     */
    public function updateCustody(UpdateCustodyStepRequest $request, ServiceOrderPickup $pickup): RedirectResponse
    {
        $pickup->advanceCustodyStep(
            $request->validated('step'),
            $request->user(),
            $request->validated('extra_info')
        );

        return back()->with('status', 'Paso de cadena de custodia actualizado correctamente.');
    }
}

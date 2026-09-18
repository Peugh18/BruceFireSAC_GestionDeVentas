<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentStoreRequest;
use App\Http\Requests\EquipmentUpdateRequest;
use App\Models\Client;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Equipment::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $equipment = Equipment::query()
            ->with('client:id,codigo,razon_social')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('codigo', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('serie_fabricante', 'like', "%{$search}%")
                        ->orWhere('marca', 'like', "%{$search}%")
                        ->orWhere('tipo_equipo', 'like', "%{$search}%");
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('equipment/index', [
            'equipment' => $equipment,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Display a JSON listing of the equipment that belongs to the given client.
     *
     * Used by the client profile's "Equipos" tab to load equipment without
     * requiring a full page navigation.
     */
    public function forClient(Client $client): JsonResponse
    {
        $this->authorize('viewAny', Equipment::class);

        $equipment = Equipment::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $equipment]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Equipment::class);

        $clients = Client::query()
            ->where('activo', true)
            ->with(['sites:id,client_id,nombre', 'vehicles:id,client_id,placa'])
            ->orderBy('razon_social')
            ->get(['id', 'codigo', 'razon_social']);

        return Inertia::render('equipment/create', [
            'clients' => $clients,
            'defaultClientId' => $request->integer('client_id') ?: null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EquipmentStoreRequest $request): RedirectResponse
    {
        $equipment = Equipment::create($request->validated());

        return to_route('equipment.show', $equipment)->with('status', 'Equipo registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment): Response
    {
        $this->authorize('view', $equipment);

        $equipment->load([
            'client:id,codigo,razon_social',
            'clientSite:id,client_id,nombre',
            'vehicle:id,client_id,placa',
            'transfers' => fn ($query) => $query->orderByDesc('fecha')->orderByDesc('id'),
            'transfers.origenClient:id,razon_social',
            'transfers.destinoClient:id,razon_social',
            'transfers.responsable:id,name',
            'events' => fn ($query) => $query->orderByDesc('fecha')->orderByDesc('id'),
            'events.user:id,name',
        ]);

        $clients = Client::query()
            ->where('activo', true)
            ->with('sites:id,client_id,nombre')
            ->orderBy('razon_social')
            ->get(['id', 'codigo', 'razon_social']);

        return Inertia::render('equipment/show', [
            'equipment' => $equipment,
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equipment $equipment): Response
    {
        $this->authorize('update', $equipment);

        $equipment->load(['client:id,codigo,razon_social', 'vehicle:id,client_id,placa']);

        $vehicles = $equipment->client
            ? $equipment->client->vehicles()->get(['id', 'client_id', 'placa'])
            : collect();

        return Inertia::render('equipment/edit', [
            'equipment' => $equipment,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EquipmentUpdateRequest $request, Equipment $equipment): RedirectResponse
    {
        $equipment->update($request->validated());

        return to_route('equipment.show', $equipment)->with('status', 'Equipo actualizado correctamente.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientStoreRequest;
use App\Http\Requests\ClientUpdateRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $clients = Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('codigo', 'like', "%{$search}%")
                        ->orWhere('razon_social', 'like', "%{$search}%")
                        ->orWhere('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('numero_documento', 'like', "%{$search}%");
                });
            })
            ->when($estado === 'activo', fn ($query) => $query->where('activo', true))
            ->when($estado === 'inactivo', fn ($query) => $query->where('activo', false))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientStoreRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return to_route('clients.show', $client)->with('status', 'Cliente creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $client->load(['sites' => fn ($query) => $query->orderByDesc('id'), 'vehicles' => fn ($query) => $query->orderByDesc('id')]);

        return Inertia::render('clients/show', [
            'client' => $client,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClientUpdateRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return to_route('clients.show', $client)->with('status', 'Cliente actualizado correctamente.');
    }

    /**
     * Lightweight search for the client combobox.
     * Returns max 20 active clients matching razon_social or numero_documento,
     * including their active sites and vehicles for immediate use in forms.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $q = $request->string('q')->trim()->toString();

        $clients = Client::query()
            ->where('activo', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('numero_documento', 'like', "%{$q}%")
                        ->orWhere('nombre_comercial', 'like', "%{$q}%");
                });
            })
            ->with([
                'sites' => fn ($q) => $q->where('activo', true)->select(['id', 'client_id', 'nombre', 'direccion', 'tipo']),
                'vehicles' => fn ($q) => $q->where('activo', true)->select(['id', 'client_id', 'placa', 'marca', 'modelo']),
            ])
            ->select(['id', 'razon_social', 'nombre_comercial', 'numero_documento', 'tipo_documento'])
            ->orderBy('razon_social')
            ->limit(20)
            ->get();

        return response()->json($clients);
    }
}

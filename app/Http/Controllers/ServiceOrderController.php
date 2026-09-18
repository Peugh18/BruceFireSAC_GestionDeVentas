<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignServiceOrderRequest;
use App\Http\Requests\IndexServiceOrderRequest;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderStatusRequest;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    public function index(IndexServiceOrderRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $estado = $request->string('estado')->toString();
        $technicianId = $request->integer('tecnico_user_id');

        $orders = ServiceOrder::query()
            ->with(['client:id,razon_social', 'tecnico:id,name'])
            ->withCount('equipment')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('codigo', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $query) => $query
                            ->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%"))
                        ->orWhereHas('equipment', fn (Builder $query) => $query->where('codigo', 'like', "%{$search}%"));
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->when($technicianId, fn (Builder $query) => $query->where('tecnico_user_id', $technicianId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('service-orders/index', [
            'orders' => $orders,
            'filters' => ['search' => $search, 'estado' => $estado, 'tecnico_user_id' => $technicianId ?: null],
            'technicians' => User::permission('service_orders.execute')->orderBy('name')->get(['id', 'name']),
            'statuses' => ServiceOrder::STATUS_LABELS,
            'serviceTypes' => ServiceOrder::SERVICE_TYPES,
            'priorities' => ServiceOrder::PRIORITIES,
            'can' => ['create' => $request->user()->can('create', ServiceOrder::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ServiceOrder::class);
        $request->validate(['client_id' => ['nullable', 'integer', 'exists:clients,id']]);
        $clientId = $request->integer('client_id');
        $canAssign = $request->user()->can('service_orders.assign');

        return Inertia::render('service-orders/create', [
            'clients' => Client::query()->where('activo', true)
                ->with([
                    'sites' => fn ($query) => $query->where('activo', true)->select('id', 'client_id', 'nombre'),
                    'vehicles' => fn ($query) => $query->where('activo', true)->select('id', 'client_id', 'placa'),
                ])->orderBy('razon_social')->get(['id', 'codigo', 'razon_social']),
            'equipment' => fn () => $clientId
                ? Equipment::query()->where('client_id', $clientId)->orderBy('codigo')
                    ->get(['id', 'client_id', 'client_site_id', 'vehicle_id', 'codigo', 'tipo_equipo', 'capacidad'])
                : [],
            'defaultClientId' => $clientId ?: null,
            'technicians' => $canAssign
                ? User::permission('service_orders.execute')->orderBy('name')->get(['id', 'name'])
                : [],
            'canAssign' => $canAssign,
            'serviceTypes' => ServiceOrder::SERVICE_TYPES,
            'priorities' => ServiceOrder::PRIORITIES,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreServiceOrderRequest $request): RedirectResponse
    {
        $order = DB::transaction(function () use ($request): ServiceOrder {
            $order = ServiceOrder::create($request->safe()->except(['equipment_ids']));
            $order->equipment()->attach($request->validated('equipment_ids'));

            return $order;
        });

        return to_route('service-orders.show', $order)->with('status', 'Orden de servicio creada correctamente.');
    }

    public function show(Request $request, ServiceOrder $serviceOrder): Response
    {
        $this->authorize('view', $serviceOrder);
        $serviceOrder->load([
            'client:id,codigo,razon_social',
            'clientSite:id,nombre',
            'vehicle:id,placa',
            'tecnico:id,name',
            'equipment:id,codigo,tipo_equipo,capacidad',
            'statusHistory' => fn ($query) => $query->orderByDesc('id'),
            'statusHistory.user:id,name',
        ]);

        $canAssign = $request->user()->can('assign', $serviceOrder);

        return Inertia::render('service-orders/show', [
            'order' => $serviceOrder,
            'transitions' => array_values(array_filter(
                $serviceOrder->allowedTransitions(),
                fn (string $status): bool => $request->user()->can('transition', [$serviceOrder, $status]),
            )),
            'statuses' => ServiceOrder::STATUS_LABELS,
            'serviceTypes' => ServiceOrder::SERVICE_TYPES,
            'priorities' => ServiceOrder::PRIORITIES,
            'canAssign' => $canAssign,
            'technicians' => $canAssign
                ? User::permission('service_orders.execute')->orderBy('name')->get(['id', 'name'])
                : [],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(AssignServiceOrderRequest $request, ServiceOrder $serviceOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceOrder): void {
            $order = ServiceOrder::query()->lockForUpdate()->findOrFail($serviceOrder->id);
            $this->authorize('assign', $order);
            $order->update($request->safe()->only(['tecnico_user_id']));
        });

        return to_route('service-orders.show', $serviceOrder)->with('status', 'Técnico asignado correctamente.');
    }

    public function changeStatus(UpdateServiceOrderStatusRequest $request, ServiceOrder $serviceOrder): RedirectResponse
    {
        $serviceOrder->transitionTo(
            $request->validated('estado'),
            $request->user(),
            $request->validated('observaciones'),
        );

        return to_route('service-orders.show', $serviceOrder)->with('status', 'Estado actualizado correctamente.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeficiencyRequest;
use App\Http\Requests\UpdateDeficiencyStatusRequest;
use App\Models\Deficiency;
use App\Models\DeficiencyAuthorization;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeficiencyController extends Controller
{
    /**
     * Display a listing of deficiencies.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Deficiency::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $deficiencies = Deficiency::query()
            ->with(['serviceOrder.client', 'equipment', 'catalogItem', 'resueltoPorUser', 'authorizations.quote'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('componente', 'like', "%{$search}%")
                    ->orWhereHas('serviceOrder', function ($q) use ($search) {
                        $q->where('codigo', 'like', "%{$search}%");
                    })
                    ->orWhereHas('equipment', function ($q) use ($search) {
                        $q->where('codigo', 'like', "%{$search}%")
                            ->orWhere('serie', 'like', "%{$search}%");
                    });
            })
            ->when($estado !== '' && $estado !== 'todos', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('deficiencies/index', [
            'deficiencies' => $deficiencies,
            'statusLabels' => Deficiency::STATUS_LABELS,
            'canalLabels' => DeficiencyAuthorization::CANAL_LABELS,
            'quotes' => Quote::query()
                ->select(['id', 'numero', 'client_id'])
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Store a newly created deficiency in storage.
     */
    public function store(StoreDeficiencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $deficiency = Deficiency::create([
            'service_order_id' => $validated['service_order_id'],
            'equipment_id' => $validated['equipment_id'],
            'checklist_item_id' => $validated['checklist_item_id'] ?? null,
            'componente' => $validated['componente'],
            'condicion' => $validated['condicion'] ?? 'observado',
            'nota' => $validated['nota'] ?? null,
            'accion_recomendada' => $validated['accion_recomendada'] ?? null,
            'repuesto_sugerido' => $validated['repuesto_sugerido'] ?? null,
            'catalog_item_id' => $validated['catalog_item_id'] ?? null,
            'requiere_autorizacion' => $validated['requiere_autorizacion'] ?? false,
            'estado' => 'detectada',
        ]);

        return back()->with('status', 'Deficiencia registrada correctamente.');
    }

    /**
     * Update deficiency state (handles transitions and registers EquipmentEvent when resolved).
     */
    public function updateStatus(UpdateDeficiencyStatusRequest $request, Deficiency $deficiency): RedirectResponse
    {
        $validated = $request->validated();

        $deficiency->transitionTo(
            $validated['estado'],
            $request->user(),
            $validated['resolucion'] ?? null
        );

        return back()->with('status', 'Estado de la deficiencia actualizado correctamente.');
    }
}

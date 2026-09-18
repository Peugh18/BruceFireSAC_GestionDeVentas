<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustInventoryRequest;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventoryMovementController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InventoryStock::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', Rule::in(['entrada', 'salida', 'ajuste'])],
            'catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), ['after_or_equal:desde'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = InventoryMovement::with(['catalogItem', 'usuario:id,name', 'reception', 'withdrawnUnits:id,salida_movement_id,serie']);
        $search = $filters['search'] ?? '';
        $query->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
            $query->where('referencia', 'like', "%{$search}%")->orWhere('motivo', 'like', "%{$search}%")
                ->orWhereHas('catalogItem', fn (Builder $query) => $query->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%"))
                ->orWhereHas('reception', fn (Builder $query) => $query->where('proveedor', 'like', "%{$search}%"));
        }));
        foreach (['tipo', 'catalog_item_id'] as $field) {
            $query->when($filters[$field] ?? null, fn (Builder $query, string|int $value) => $query->where($field, $value));
        }
        $query->when($filters['desde'] ?? null, fn (Builder $query, string $date) => $query->whereDate('fecha', '>=', $date))
            ->when($filters['hasta'] ?? null, fn (Builder $query, string $date) => $query->whereDate('fecha', '<=', $date));

        return Inertia::render('inventory/movements', [
            'movements' => $query->orderByDesc('fecha')->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search, 'tipo' => $filters['tipo'] ?? '',
                'catalog_item_id' => (string) ($filters['catalog_item_id'] ?? ''),
                'desde' => $filters['desde'] ?? '', 'hasta' => $filters['hasta'] ?? '',
            ],
        ]);
    }

    public function store(AdjustInventoryRequest $request, InventoryStock $stock): RedirectResponse
    {
        $stock->move($request->validated(), $request->user());

        return to_route('inventory.show', $stock)->with('inventory_status', 'Movimiento registrado correctamente.');
    }
}

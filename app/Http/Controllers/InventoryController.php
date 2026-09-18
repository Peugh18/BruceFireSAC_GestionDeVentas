<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInventoryStockRequest;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InventoryStock::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'low_stock' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = InventoryStock::with('catalogItem')->whereHas('catalogItem', fn (Builder $query) => $query->where('controla_stock', true));
        $lowStockCount = (clone $query)->whereColumn('stock_actual', '<', 'stock_minimo')->count();
        $search = $filters['search'] ?? '';
        $query->when($search !== '', fn (Builder $query) => $query->whereHas('catalogItem', function (Builder $query) use ($search): void {
            $query->where(fn (Builder $query) => $query->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%")->orWhere('categoria', 'like', "%{$search}%"));
        }))->when($request->boolean('low_stock'), fn (Builder $query) => $query->whereColumn('stock_actual', '<', 'stock_minimo'));

        return Inertia::render('inventory/index', [
            'stocks' => $query->orderBy('catalog_item_id')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search, 'low_stock' => $request->boolean('low_stock')],
            'lowStockCount' => $lowStockCount,
            'can' => ['receive' => $request->user()->can('receive', InventoryStock::class), 'adjust' => $request->user()->can('adjust', InventoryStock::class)],
            'status' => $request->session()->get('inventory_status'),
        ]);
    }

    public function show(Request $request, InventoryStock $stock): Response
    {
        Gate::authorize('viewAny', InventoryStock::class);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return Inertia::render('inventory/show', [
            'stock' => $stock->load('catalogItem'),
            'units' => InventoryUnit::where('catalog_item_id', $stock->catalog_item_id)->orderByDesc('en_stock')->orderBy('serie')->paginate(25)->withQueryString(),
            'canAdjust' => $stock->catalogItem->controla_stock && $request->user()->can('adjust', InventoryStock::class),
            'today' => now()->toDateString(),
            'status' => $request->session()->get('inventory_status'),
        ]);
    }

    public function update(UpdateInventoryStockRequest $request, InventoryStock $stock): RedirectResponse
    {
        $stock->update($request->validated());

        return to_route('inventory.show', $stock)->with('inventory_status', 'Stock mínimo actualizado.');
    }
}

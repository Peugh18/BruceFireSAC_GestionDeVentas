<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiveInventoryRequest;
use App\Models\CatalogItem;
use App\Models\InventoryReception;
use App\Models\InventoryStock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReceptionController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize('receive', InventoryStock::class);

        $filters = $request->validate([
            'catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
        ]);

        return Inertia::render('inventory/receive', [
            'items' => CatalogItem::where('controla_stock', true)->where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre', 'unidad', 'control_serializado']),
            'today' => now()->toDateString(),
            'defaultCatalogItemId' => $filters['catalog_item_id'] ?? null,
        ]);
    }

    public function store(ReceiveInventoryRequest $request): RedirectResponse
    {
        try {
            InventoryReception::receive($request->validated(), $request->user());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['units' => 'Una serie o código de barras ya fue registrado. Revisa las unidades.']);
        }

        return to_route('inventory.index')->with('inventory_status', 'Recepción registrada. Solo la cantidad conforme se incorporó al stock.');
    }
}

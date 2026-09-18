<?php

namespace App\Http\Controllers;

use App\Models\InventoryUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryUnitController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('sales.create'), 403);

        $filters = $request->validate([
            'catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        abort_if(blank($filters['barcode'] ?? null) && blank($filters['q'] ?? null), 422, 'Ingresa un barcode o serie para buscar.');

        $query = InventoryUnit::query()
            ->with('catalogItem')
            ->where('en_stock', true)
            ->where('conforme', true)
            ->where('estado', 'disponible')
            ->whereHas('catalogItem', fn ($query) => $query
                ->where('activo', true)
                ->where('controla_stock', true)
                ->where('control_serializado', true)
            )
            ->when($filters['catalog_item_id'] ?? null, fn ($query, int $catalogItemId) => $query->where('catalog_item_id', $catalogItemId));

        if (filled($filters['barcode'] ?? null)) {
            $barcode = $filters['barcode'];
            $units = (clone $query)
                ->where(fn ($query) => $query->where('barcode', $barcode)->orWhere('serie', $barcode))
                ->orderBy('serie')
                ->limit(10)
                ->get();
        } else {
            $search = $filters['q'];
            $units = $query
                ->where(fn ($query) => $query
                    ->where('barcode', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%")
                )
                ->orderBy('serie')
                ->limit(10)
                ->get();
        }

        return response()->json([
            'unit' => $units->count() === 1 ? $this->serializeUnit($units->first()) : null,
            'units' => $units->map(fn (InventoryUnit $unit): array => $this->serializeUnit($unit))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUnit(InventoryUnit $unit): array
    {
        return [
            'id' => $unit->id,
            'catalog_item_id' => $unit->catalog_item_id,
            'serie' => $unit->serie,
            'marca' => $unit->marca,
            'capacidad' => $unit->capacidad,
            'anio' => $unit->anio,
            'barcode' => $unit->barcode,
            'catalog_item' => $unit->catalogItem,
        ];
    }
}

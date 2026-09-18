<?php

namespace App\Services\Sales;

use App\Models\CatalogItem;
use App\Models\Equipment;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use App\Models\Sale;
use App\Models\User;

class SaleItemProcessor
{
    /**
     * Process sale items: creates SaleItem records, performs stock movements,
     * marks serialized units as sold, and creates client Equipment.
     *
     * @param  array<int, array<string, mixed>>  $itemsData
     */
    public function process(Sale $sale, array $itemsData, User $user): void
    {
        $catalogItems = CatalogItem::query()
            ->with('inventoryStock')
            ->whereIn('id', collect($itemsData)->pluck('catalog_item_id')->all())
            ->get()
            ->keyBy('id');

        foreach ($itemsData as $itemData) {
            $sale->items()->create($itemData);

            $catalogItem = $catalogItems->get($itemData['catalog_item_id']);
            if ($catalogItem?->controla_stock) {
                $stock = $catalogItem->inventoryStock ?? InventoryStock::firstOrCreate(['catalog_item_id' => $catalogItem->id]);
                $unitIds = $catalogItem->control_serializado && ! empty($itemData['inventory_unit_id'])
                    ? [(int) $itemData['inventory_unit_id']]
                    : [];

                $stock->move([
                    'tipo' => 'salida',
                    'cantidad' => $itemData['cantidad'],
                    'motivo' => 'venta',
                    'referencia' => $sale->numero,
                    'fecha' => $sale->fecha->toDateString(),
                    'unit_ids' => $unitIds,
                ], $user);

                if ($catalogItem->control_serializado && ! empty($itemData['inventory_unit_id'])) {
                    $unit = InventoryUnit::query()->findOrFail($itemData['inventory_unit_id']);
                    $unit->update(['estado' => 'vendido']);

                    Equipment::create([
                        'client_id' => $sale->client_id,
                        'client_site_id' => $sale->client_site_id,
                        'vehicle_id' => $sale->vehicle_id,
                        'origen' => 'vendido_bruce_fire',
                        'tipo_equipo' => $catalogItem->nombre,
                        'capacidad' => $unit->capacidad,
                        'marca' => $unit->marca,
                        'serie_fabricante' => $unit->serie,
                        'anio_fabricacion' => (string) $unit->anio,
                        'barcode' => $unit->barcode,
                        'estado' => 'activo',
                        'observaciones' => "Creado automaticamente por venta {$sale->numero}.",
                    ]);
                }
            }
        }
    }
}

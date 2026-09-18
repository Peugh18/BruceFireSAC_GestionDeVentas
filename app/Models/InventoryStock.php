<?php

namespace App\Models;

use Database\Factories\InventoryStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStock extends Model
{
    /** @use HasFactory<InventoryStockFactory> */
    use HasFactory;

    protected $fillable = ['catalog_item_id', 'stock_actual', 'stock_minimo'];

    protected function casts(): array
    {
        return ['stock_actual' => 'decimal:3', 'stock_minimo' => 'decimal:3'];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /** @param array{tipo: string, cantidad: numeric-string|float|int, motivo: string, fecha: string, referencia?: ?string, observacion?: ?string, unit_ids?: list<int>} $data */
    public function move(array $data, User $user): InventoryMovement
    {
        return DB::transaction(function () use ($data, $user): InventoryMovement {
            $item = CatalogItem::query()->lockForUpdate()->findOrFail($this->catalog_item_id);
            $stock = static::query()->lockForUpdate()->findOrFail($this->id);

            if (! $item->controla_stock) {
                throw ValidationException::withMessages(['cantidad' => 'Este artículo ya no controla stock.']);
            }

            $quantity = (int) round((float) $data['cantidad'] * 1000);
            $delta = $data['tipo'] === 'salida' ? -$quantity : $quantity;
            $before = (int) round((float) $stock->stock_actual * 1000);
            $after = $before + $delta;

            if ($after < 0 || $after > 999999999999) {
                throw ValidationException::withMessages(['cantidad' => 'El movimiento excede el stock disponible o el límite permitido.']);
            }

            $unitIds = $data['unit_ids'] ?? [];
            if ($item->control_serializado) {
                if ($delta >= 0 || $delta % 1000 !== 0) {
                    throw ValidationException::withMessages(['cantidad' => 'Para incorporar unidades serializadas utiliza una recepción. Las salidas deben ser enteras.']);
                }

                $units = InventoryUnit::query()->where('catalog_item_id', $item->id)
                    ->whereIn('id', $unitIds)->where('en_stock', true)->where('conforme', true)
                    ->where('estado', 'disponible')->lockForUpdate()->get();

                if ($units->count() !== intdiv(abs($delta), 1000) || $units->count() !== count($unitIds)) {
                    throw ValidationException::withMessages(['unit_ids' => 'Selecciona exactamente las unidades disponibles que salen del almacén.']);
                }
            } elseif ($unitIds !== []) {
                throw ValidationException::withMessages(['unit_ids' => 'Este artículo no utiliza unidades serializadas.']);
            }

            $movement = InventoryMovement::create([
                'catalog_item_id' => $item->id,
                'tipo' => $data['tipo'],
                'cantidad' => $quantity / 1000,
                'stock_antes' => $before / 1000,
                'stock_despues' => $after / 1000,
                'motivo' => $data['motivo'],
                'referencia' => $data['referencia'] ?? null,
                'observacion' => $data['observacion'] ?? null,
                'fecha' => $data['fecha'],
                'usuario_id' => $user->id,
            ]);

            if ($item->control_serializado) {
                InventoryUnit::whereIn('id', $unitIds)->update(['en_stock' => false, 'salida_movement_id' => $movement->id]);
            }

            $stock->update(['stock_actual' => $after / 1000]);

            return $movement;
        }, 3);
    }
}

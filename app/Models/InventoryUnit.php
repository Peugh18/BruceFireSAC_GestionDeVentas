<?php

namespace App\Models;

use Database\Factories\InventoryUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryUnit extends Model
{
    /** @use HasFactory<InventoryUnitFactory> */
    use HasFactory;

    protected $fillable = ['catalog_item_id', 'inventory_reception_id', 'salida_movement_id', 'serie', 'marca', 'capacidad', 'anio', 'barcode', 'estado', 'conforme', 'en_stock'];

    protected function casts(): array
    {
        return ['anio' => 'integer', 'conforme' => 'boolean', 'en_stock' => 'boolean'];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(InventoryReception::class, 'inventory_reception_id');
    }

    public function salidaMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'salida_movement_id');
    }
}

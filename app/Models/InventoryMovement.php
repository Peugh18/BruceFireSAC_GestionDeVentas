<?php

namespace App\Models;

use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected $fillable = ['catalog_item_id', 'inventory_reception_id', 'tipo', 'cantidad', 'stock_antes', 'stock_despues', 'motivo', 'referencia', 'usuario_id', 'fecha', 'observacion'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3', 'stock_antes' => 'decimal:3', 'stock_despues' => 'decimal:3', 'fecha' => 'date:Y-m-d'];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(InventoryReception::class, 'inventory_reception_id');
    }

    public function withdrawnUnits(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'salida_movement_id');
    }
}

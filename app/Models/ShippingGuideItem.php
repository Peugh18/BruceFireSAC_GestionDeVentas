<?php

namespace App\Models;

use Database\Factories\ShippingGuideItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingGuideItem extends Model
{
    /** @use HasFactory<ShippingGuideItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'shipping_guide_id',
        'sale_item_id',
        'descripcion',
        'cantidad',
        'unidad',
        'peso',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'peso' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ShippingGuide, $this> */
    public function shippingGuide(): BelongsTo
    {
        return $this->belongsTo(ShippingGuide::class);
    }

    /** @return BelongsTo<SaleItem, $this> */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}

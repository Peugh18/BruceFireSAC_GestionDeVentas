<?php

namespace App\Models;

use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'checklist_id',
        'componente',
        'condicion',
        'foto_path',
        'nota',
        'accion_recomendada',
    ];

    /**
     * @return BelongsTo<ServiceOrderChecklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(ServiceOrderChecklist::class, 'checklist_id');
    }

    /**
     * @return HasOne<Deficiency, $this>
     */
    public function deficiency(): HasOne
    {
        return $this->hasOne(Deficiency::class, 'checklist_item_id');
    }
}

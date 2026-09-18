<?php

namespace App\Models;

use Database\Factories\DeficiencyAuthorizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeficiencyAuthorization extends Model
{
    /** @use HasFactory<DeficiencyAuthorizationFactory> */
    use HasFactory;

    public const CANAL_LABELS = [
        'whatsapp' => 'WhatsApp',
        'presencial' => 'Presencial',
    ];

    /** @var list<string> */
    protected $fillable = [
        'deficiency_id',
        'quote_id',
        'autorizado_por',
        'canal',
        'fecha',
        'observacion',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Deficiency, $this> */
    public function deficiency(): BelongsTo
    {
        return $this->belongsTo(Deficiency::class);
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}

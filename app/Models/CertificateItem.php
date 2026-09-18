<?php

namespace App\Models;

use Database\Factories\CertificateItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateItem extends Model
{
    /** @use HasFactory<CertificateItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'certificate_id',
        'equipment_id',
    ];

    /** @return BelongsTo<Certificate, $this> */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /** @return BelongsTo<Equipment, $this> */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}

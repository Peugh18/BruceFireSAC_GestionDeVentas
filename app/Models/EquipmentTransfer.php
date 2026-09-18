<?php

namespace App\Models;

use Database\Factories\EquipmentTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentTransfer extends Model
{
    /** @use HasFactory<EquipmentTransferFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_id',
        'origen_client_id',
        'origen_client_site_id',
        'destino_client_id',
        'destino_client_site_id',
        'fecha',
        'motivo',
        'responsable_user_id',
        'observacion',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function origenClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'origen_client_id');
    }

    /**
     * @return BelongsTo<ClientSite, $this>
     */
    public function origenClientSite(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'origen_client_site_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function destinoClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'destino_client_id');
    }

    /**
     * @return BelongsTo<ClientSite, $this>
     */
    public function destinoClientSite(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'destino_client_site_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }
}

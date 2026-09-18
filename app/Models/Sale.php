<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'numero',
        'quote_id',
        'client_id',
        'client_site_id',
        'vehicle_id',
        'vendedor_user_id',
        'fecha',
        'condicion_pago',
        'subtotal',
        'igv',
        'total',
        'estado',
        'observaciones',
        'service_order_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<ClientSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'client_site_id');
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_user_id');
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return HasMany<SalePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * @return HasMany<SaleInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(SaleInstallment::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (empty($sale->numero)) {
                $sale->numero = self::nextNumero();
            }
        });
    }

    /**
     * Generate the next sequential sale code (e.g. VEN-000001).
     */
    public static function nextNumero(): string
    {
        $lastNumero = self::query()
            ->where('numero', 'like', 'VEN-%')
            ->orderByDesc('numero')
            ->value('numero');

        $lastNumber = $lastNumero ? (int) substr($lastNumero, 4) : 0;

        return sprintf('VEN-%06d', $lastNumber + 1);
    }
}

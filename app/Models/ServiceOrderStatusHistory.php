<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderStatusHistory extends Model
{
    /** @var list<string> */
    protected $fillable = ['service_order_id', 'estado_anterior', 'estado', 'user_id', 'observaciones'];

    /** @return BelongsTo<ServiceOrder, $this> */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pickups.create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_order_id' => ['required', 'exists:service_orders,id'],
            'client_site_id' => ['nullable', 'exists:client_sites,id'],
            'contacto' => ['required', 'string', 'max:255'],
            'fecha_hora_recojo' => ['required', 'date'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'conforme_nombre' => ['nullable', 'string', 'max:255'],
            'conforme_dni' => ['nullable', 'string', 'max:20'],
            'conforme_firma' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['nullable', 'image', 'max:10240'],
        ];
    }
}

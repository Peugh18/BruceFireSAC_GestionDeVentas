<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('service_order'));
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in($this->route('service_order')->allowedTransitions())],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'precio' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'estado.in' => 'La transición solicitada no es válida para el estado actual de la orden.',
            'precio.prohibited' => 'Las órdenes de servicio no gestionan precios.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustodyStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pickups.custody');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(['recibido_planta', 'entregado', 'recibido_cliente'])],
            'extra_info' => ['nullable', 'string', 'max:255'],
        ];
    }
}

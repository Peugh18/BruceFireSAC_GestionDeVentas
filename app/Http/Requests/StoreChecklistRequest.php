<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('checklists.fill');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::in(['borrador', 'completado'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.componente' => ['required', 'string', 'max:100'],
            'items.*.condicion' => ['required', Rule::in(['conforme', 'observado', 'no_aplica'])],
            'items.*.nota' => ['nullable', 'string'],
            'items.*.accion_recomendada' => ['nullable', 'string'],
            'items.*.repuesto_sugerido' => ['nullable', 'string'],
            'items.*.catalog_item_id' => ['nullable', 'exists:catalog_items,id'],
            'items.*.requiere_autorizacion' => ['nullable', 'boolean'],
        ];
    }
}

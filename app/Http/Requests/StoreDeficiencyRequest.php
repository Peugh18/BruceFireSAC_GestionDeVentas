<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeficiencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('deficiencies.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_order_id' => ['required', 'exists:service_orders,id'],
            'equipment_id' => ['required', 'exists:equipment,id'],
            'checklist_item_id' => ['nullable', 'exists:checklist_items,id'],
            'componente' => ['required', 'string', 'max:100'],
            'condicion' => ['nullable', 'string', 'max:50'],
            'nota' => ['nullable', 'string'],
            'accion_recomendada' => ['nullable', 'string'],
            'repuesto_sugerido' => ['nullable', 'string'],
            'catalog_item_id' => ['nullable', 'exists:catalog_items,id'],
            'requiere_autorizacion' => ['boolean'],
        ];
    }
}

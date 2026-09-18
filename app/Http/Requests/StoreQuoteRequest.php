<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('quotes.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'client_site_id' => ['nullable', 'exists:client_sites,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'fecha' => ['required', 'date'],
            'vigencia' => ['required', 'date', 'after_or_equal:fecha'],
            'condicion_propuesta' => ['required', Rule::in(['contado', 'credito'])],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['required', 'numeric', 'gte:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}

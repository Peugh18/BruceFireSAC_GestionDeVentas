<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'client_site_id' => ['nullable', 'exists:client_sites,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'fecha' => ['required', 'date'],
            'condicion_pago' => ['required', Rule::in(['contado', 'credito'])],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['required', 'numeric', 'gte:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'gte:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.forma_pago' => ['required_with:payments', Rule::in(['efectivo', 'transferencia', 'yape', 'plin', 'pos', 'deposito', 'otro'])],
            'payments.*.monto' => ['required_with:payments', 'numeric', 'gt:0'],
            'payments.*.referencia' => ['nullable', 'string'],
            'installments' => ['nullable', 'array'],
            'installments.*.numero_cuota' => ['required_with:installments', 'integer', 'gt:0'],
            'installments.*.monto' => ['required_with:installments', 'numeric', 'gt:0'],
            'installments.*.fecha_vencimiento' => ['required_with:installments', 'date'],
        ];
    }
}

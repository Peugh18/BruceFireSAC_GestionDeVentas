<?php

namespace App\Http\Requests;

use App\Models\ShippingGuide;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShippingGuideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('shipping_guides.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['nullable', 'exists:sales,id'],
            'motivo_traslado' => ['required', Rule::in(array_keys(ShippingGuide::MOTIVO_LABELS))],
            'fecha_inicio' => ['required', 'date'],
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],

            'destinatario_client_id' => ['nullable', 'exists:clients,id'],
            'destinatario_nombre' => ['required_without:destinatario_client_id', 'nullable', 'string', 'max:150'],
            'destinatario_documento' => ['nullable', 'string', 'max:20'],

            'peso_total' => ['required', 'numeric', 'min:0.01'],
            'modalidad' => ['required', Rule::in(array_keys(ShippingGuide::MODALIDAD_LABELS))],

            'transportista_razon_social' => ['required_if:modalidad,transporte_publico', 'nullable', 'string', 'max:150'],
            'transportista_ruc' => ['required_if:modalidad,transporte_publico', 'nullable', 'digits:11'],

            'vehiculo_placa' => ['required_if:modalidad,transporte_privado', 'nullable', 'string', 'max:10'],
            'conductor_nombre' => ['required_if:modalidad,transporte_privado', 'nullable', 'string', 'max:150'],
            'conductor_licencia' => ['required_if:modalidad,transporte_privado', 'nullable', 'string', 'max:20'],

            'observaciones' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['nullable', 'exists:sale_items,id'],
            'items.*.descripcion' => ['required', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items.*.unidad' => ['nullable', 'string', 'max:10'],
            'items.*.peso' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

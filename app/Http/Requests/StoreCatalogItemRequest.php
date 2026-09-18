<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CatalogItem::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['producto', 'servicio', 'repuesto'])],
            'categoria' => ['required', 'string', 'max:100'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'unidad' => ['required', 'string', 'max:30'],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'aplica_igv' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
            'controla_stock' => ['exclude_unless:tipo,producto', 'required', 'boolean'],
            'control_serializado' => ['exclude_unless:tipo,producto', 'required', 'boolean'],
            'genera_barcode' => ['exclude_unless:tipo,producto', 'required', 'boolean'],
            'tipo_tecnico' => ['exclude_unless:tipo,servicio', 'required', 'string', 'max:100'],
            'requiere_orden' => ['exclude_unless:tipo,servicio', 'required', 'boolean'],
            'requiere_certificado' => ['exclude_unless:tipo,servicio', 'required', 'boolean'],
            'checklist_aplicable' => ['exclude_unless:tipo,servicio', 'nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'boolean' => 'Selecciona una opción válida para :attribute.',
            'tipo.in' => 'Selecciona producto, servicio o repuesto.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.decimal' => 'El precio debe tener como máximo dos decimales.',
            'precio.min' => 'El precio no puede ser negativo.',
            'precio.max' => 'El precio no puede superar 9999999999.99.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'categoria' => 'categoría', 'descripcion' => 'descripción',
            'aplica_igv' => 'aplica IGV', 'controla_stock' => 'controla stock',
            'control_serializado' => 'control serializado', 'genera_barcode' => 'genera código de barras',
            'tipo_tecnico' => 'tipo de técnico', 'requiere_orden' => 'requiere orden',
            'requiere_certificado' => 'requiere certificado', 'checklist_aplicable' => 'checklist aplicable',
        ];
    }
}

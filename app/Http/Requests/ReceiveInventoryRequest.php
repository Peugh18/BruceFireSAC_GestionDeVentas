<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
use App\Models\InventoryStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReceiveInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', InventoryStock::class);
    }

    public function rules(): array
    {
        return [
            'catalog_item_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('controla_stock', true)->where('activo', true)],
            'proveedor' => ['required', 'string', 'max:255'],
            'documento_referencia' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'cantidad' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999.999'],
            'cantidad_conforme' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999.999'],
            'cantidad_observada' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999.999'],
            'observacion' => ['nullable', 'string', 'max:5000'],
            'units' => ['sometimes', 'array', 'max:200'],
            'units.*' => ['array:serie,marca,capacidad,anio,barcode,conforme'],
            'units.*.serie' => ['required', 'string', 'max:100', 'distinct:ignore_case', Rule::unique('inventory_units', 'serie')],
            'units.*.marca' => ['required', 'string', 'max:100'],
            'units.*.capacidad' => ['required', 'string', 'max:100'],
            'units.*.anio' => ['required', 'integer', 'between:1900,'.now()->year],
            'units.*.barcode' => ['nullable', 'string', 'max:100', 'distinct:ignore_case', Rule::unique('inventory_units', 'barcode')],
            'units.*.conforme' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $total = (int) round((float) $this->input('cantidad') * 1000);
            $accepted = (int) round((float) $this->input('cantidad_conforme') * 1000);
            $observed = (int) round((float) $this->input('cantidad_observada') * 1000);
            if ($total !== $accepted + $observed) {
                $validator->errors()->add('cantidad', 'La cantidad debe ser igual a conforme más observada.');
            }
            if ($observed > 0 && ! $this->filled('observacion')) {
                $validator->errors()->add('observacion', 'Describe la observación de las unidades retenidas.');
            }

            $item = CatalogItem::find($this->integer('catalog_item_id'));
            $units = $this->input('units', []);
            if (! $item->control_serializado) {
                if ($units !== []) {
                    $validator->errors()->add('units', 'Este artículo no utiliza unidades serializadas.');
                }

                return;
            }

            if ($total % 1000 !== 0 || $accepted % 1000 !== 0 || $observed % 1000 !== 0 || count($units) !== intdiv($total, 1000)) {
                $validator->errors()->add('units', 'Registra una serie por cada unidad recibida, con cantidades enteras (máximo 200).');
            }
            if (collect($units)->where('conforme', true)->count() !== intdiv($accepted, 1000)) {
                $validator->errors()->add('units', 'Las series conformes y observadas deben coincidir con las cantidades declaradas.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'decimal' => 'El campo :attribute admite hasta tres decimales.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'max' => 'El campo :attribute supera el límite de :max.',
            'array' => 'La lista de unidades tiene un formato inválido.',
            'boolean' => 'Selecciona una condición válida.',
            'between' => 'El año debe estar entre :min y :max.',
            'date_format' => 'Ingresa una fecha válida.',
            'before_or_equal' => 'La fecha no puede ser futura.',
            'catalog_item_id.exists' => 'Selecciona un artículo activo que controle stock.',
            'unique' => 'El valor de :attribute ya está registrado.',
            'distinct' => 'No repitas :attribute en la recepción.',
        ];
    }

    public function attributes(): array
    {
        return [
            'catalog_item_id' => 'artículo', 'documento_referencia' => 'documento de referencia',
            'cantidad_conforme' => 'cantidad conforme', 'cantidad_observada' => 'cantidad observada',
            'observacion' => 'observación', 'units' => 'unidades',
            'units.*.serie' => 'serie', 'units.*.marca' => 'marca', 'units.*.capacidad' => 'capacidad',
            'units.*.anio' => 'año', 'units.*.barcode' => 'código de barras', 'units.*.conforme' => 'conformidad',
        ];
    }
}

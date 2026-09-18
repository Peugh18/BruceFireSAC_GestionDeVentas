<?php

namespace App\Http\Requests;

use App\Models\InventoryStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adjust', InventoryStock::class);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['salida', 'ajuste'])],
            'cantidad' => ['required', 'numeric', 'decimal:0,3', 'not_in:0', 'between:-999999.999,999999.999', Rule::when($this->input('tipo') === 'salida', ['min:0.001'])],
            'motivo' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observacion' => ['nullable', 'string', 'max:5000'],
            'unit_ids' => ['sometimes', 'array', 'max:200'],
            'unit_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'numeric' => 'La cantidad debe ser un número.',
            'decimal' => 'La cantidad admite hasta tres decimales.',
            'not_in' => 'El movimiento debe cambiar el stock.',
            'between' => 'La cantidad excede el rango permitido.',
            'min' => 'La cantidad de salida debe ser positiva.',
            'tipo.in' => 'Selecciona salida o ajuste.',
            'date_format' => 'Ingresa una fecha válida.',
            'before_or_equal' => 'La fecha no puede ser futura.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute supera el límite de :max.',
            'distinct' => 'No selecciones una unidad más de una vez.',
            'integer' => 'Selecciona unidades válidas.',
            'array' => 'Selecciona una lista de unidades válida.',
        ];
    }
}

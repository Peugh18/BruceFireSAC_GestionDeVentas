<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionPaymentRequest extends FormRequest
{
    public const PAYMENT_METHODS = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia bancaria',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'pos' => 'POS / Tarjeta',
        'deposito' => 'Depósito',
        'otro' => 'Otro',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('registerPayment', $this->route('installment'));
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'forma_pago' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'referencia' => ['nullable', 'string', 'max:255'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$this->route('sale')->fecha->toDateString()],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'monto.required' => 'Ingresa el monto recibido.',
            'monto.numeric' => 'Ingresa un monto válido.',
            'monto.min' => 'El pago debe ser de al menos S/ 0.01.',
            'monto.regex' => 'Ingresa un monto con un máximo de dos decimales.',
            'forma_pago.required' => 'Selecciona el método de pago.',
            'forma_pago.in' => 'Selecciona un método de pago válido.',
            'fecha.required' => 'Indica la fecha del pago.',
            'fecha.date_format' => 'Ingresa una fecha válida.',
            'fecha.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'fecha.after_or_equal' => 'El pago no puede ser anterior a la venta.',
            'idempotency_key.required' => 'Recarga la página antes de registrar el pago.',
            'idempotency_key.uuid' => 'Recarga la página antes de registrar el pago.',
        ];
    }
}

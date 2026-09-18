<?php

namespace App\Http\Requests;

use App\Models\CreditDebitNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCreditDebitNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('billing.credit_note');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(CreditDebitNote::TIPO_LABELS))],
            'motivo' => ['required', 'string'],
            'detalle' => ['required', 'string', 'max:1000'],
            'importe' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
        ];
    }

    /**
     * The valid motivo codes depend on the chosen tipo (SUNAT catalogs 09/10).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tipo = $this->string('tipo')->toString();
            $motivo = $this->string('motivo')->toString();

            if ($tipo === '' || $motivo === '') {
                return;
            }

            if (! array_key_exists($motivo, CreditDebitNote::motivoLabels($tipo))) {
                $validator->errors()->add('motivo', 'El motivo seleccionado no es valido para el tipo de nota elegido.');
            }
        });
    }
}

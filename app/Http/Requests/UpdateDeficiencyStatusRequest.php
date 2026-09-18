<?php

namespace App\Http\Requests;

use App\Models\Deficiency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeficiencyStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $deficiency = $this->route('deficiency');
        $nextStatus = (string) $this->input('estado');

        return $deficiency instanceof Deficiency && $this->user()->can('updateStatus', [$deficiency, $nextStatus]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                Rule::in([
                    'detectada',
                    'esperando_autorizacion',
                    'autorizada',
                    'rechazada',
                    'en_correccion',
                    'resuelta',
                ]),
            ],
            'resolucion' => ['nullable', 'string'],
        ];
    }
}

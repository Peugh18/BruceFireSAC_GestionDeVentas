<?php

namespace App\Http\Requests;

use App\Models\Deficiency;
use App\Models\DeficiencyAuthorization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeficiencyAuthorizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $deficiency = $this->route('deficiency');

        return $deficiency instanceof Deficiency && $this->user()->can('updateStatus', [$deficiency, 'autorizada']);
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
            'autorizado_por' => ['required', 'string', 'max:150'],
            'canal' => ['required', Rule::in(array_keys(DeficiencyAuthorization::CANAL_LABELS))],
            'fecha' => ['required', 'date'],
            'observacion' => ['nullable', 'string'],
        ];
    }
}

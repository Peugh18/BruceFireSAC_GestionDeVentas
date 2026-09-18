<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('service_order'));
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'tecnico_user_id' => ['present', ...self::technicianRules()],
            'estado' => ['prohibited'],
            'precio' => ['prohibited'],
        ];
    }

    /** @return array<int, mixed> */
    public static function technicianRules(): array
    {
        return [
            'nullable', 'integer',
            Rule::in(User::permission('service_orders.execute')->pluck('id')->all()),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['tecnico_user_id.in' => 'Selecciona un usuario con permiso para ejecutar servicios.'];
    }
}

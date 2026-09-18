<?php

namespace App\Http\Requests;

use App\Models\ServiceOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', ServiceOrder::class);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(array_keys(ServiceOrder::STATUS_LABELS))],
            'tecnico_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}

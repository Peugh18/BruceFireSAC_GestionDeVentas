<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('users.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists(Role::class, 'name')->where('guard_name', 'web')],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * Prevent deactivating or reassigning the last active Gerente,
     * which would leave the system with nobody able to manage it.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User|null $target */
            $target = $this->route('user');

            if (! $target instanceof User || ! $target->hasRole('Gerente')) {
                return;
            }

            $remainingActiveAdmins = User::role('Gerente')
                ->where('activo', true)
                ->where('id', '!=', $target->id)
                ->count();

            if ($remainingActiveAdmins > 0) {
                return;
            }

            if (! $this->boolean('activo')) {
                $validator->errors()->add('activo', 'No puedes desactivar al unico Gerente activo del sistema.');
            }

            if ($this->string('role')->toString() !== 'Gerente') {
                $validator->errors()->add('role', 'No puedes quitarle el rol de Gerente al unico Gerente activo del sistema.');
            }
        });
    }
}

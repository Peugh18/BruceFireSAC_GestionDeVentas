<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * The Gerente role is the only source of users.manage/roles.manage
     * in the seeded system: stripping either would leave nobody able to
     * administer users, roles or permissions.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Role|null $role */
            $role = $this->route('role');

            if (! $role instanceof Role || $role->name !== 'Gerente') {
                return;
            }

            $permissions = $this->input('permissions', []);

            foreach (['users.manage', 'roles.manage'] as $required) {
                if (! in_array($required, $permissions, true) && Permission::where('name', $required)->exists()) {
                    $validator->errors()->add('permissions', "No puedes quitarle el permiso \"{$required}\" al rol Gerente: el sistema quedaria sin nadie que pueda administrarlo.");
                }
            }
        });
    }
}

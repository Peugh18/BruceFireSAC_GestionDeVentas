<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    /**
     * Determine whether the user can update the model's permissions.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }
}

<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('equipment.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('equipment.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.update');
    }

    /**
     * Determine whether the user can transfer the model.
     */
    public function transfer(User $user, Equipment $equipment): bool
    {
        return $user->can('equipment.transfer');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * An equipment with history is never deleted; deletion is always denied.
     */
    public function delete(User $user, Equipment $equipment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Equipment $equipment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Equipment $equipment): bool
    {
        return false;
    }
}

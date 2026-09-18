<?php

namespace App\Policies;

use App\Models\Deficiency;
use App\Models\User;

class DeficiencyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('deficiencies.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deficiency $deficiency): bool
    {
        return $user->can('deficiencies.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('deficiencies.create');
    }

    /**
     * Determine whether the user can authorize or change state of deficiency.
     */
    public function updateStatus(User $user, Deficiency $deficiency, string $nextStatus): bool
    {
        if ($nextStatus === 'autorizada' || $nextStatus === 'esperando_autorizacion') {
            return $user->can('deficiencies.authorize');
        }

        if ($nextStatus === 'resuelta' || $nextStatus === 'en_correccion') {
            return $user->can('deficiencies.resolve');
        }

        return $user->can('deficiencies.create');
    }
}

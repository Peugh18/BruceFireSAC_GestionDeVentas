<?php

namespace App\Policies;

use App\Models\ServiceOrderChecklist;
use App\Models\User;

class ServiceOrderChecklistPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('checklists.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?ServiceOrderChecklist $checklist = null): bool
    {
        return $user->can('checklists.view');
    }

    /**
     * Determine whether the user can fill/update the checklist.
     */
    public function fill(User $user, ?ServiceOrderChecklist $checklist = null): bool
    {
        return $user->can('checklists.fill');
    }
}

<?php

namespace App\Policies;

use App\Models\ServiceOrderPickup;
use App\Models\User;

class ServiceOrderPickupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pickups.view');
    }

    public function view(User $user, ServiceOrderPickup $pickup): bool
    {
        return $user->can('pickups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pickups.create');
    }

    public function updateCustody(User $user, ServiceOrderPickup $pickup): bool
    {
        return $user->can('pickups.custody');
    }
}

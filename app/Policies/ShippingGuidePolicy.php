<?php

namespace App\Policies;

use App\Models\ShippingGuide;
use App\Models\User;

class ShippingGuidePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shipping_guides.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShippingGuide $shippingGuide): bool
    {
        return $user->can('shipping_guides.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('shipping_guides.create');
    }
}

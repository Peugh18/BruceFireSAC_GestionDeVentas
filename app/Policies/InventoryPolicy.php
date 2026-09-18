<?php

namespace App\Policies;

use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function receive(User $user): bool
    {
        return $user->can('inventory.view') && $user->can('inventory.receive');
    }

    public function adjust(User $user): bool
    {
        return $user->can('inventory.view') && $user->can('inventory.adjust');
    }
}

<?php

namespace App\Policies;

use App\Models\SaleInstallment;
use App\Models\User;

class CollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function registerPayment(User $user, SaleInstallment $installment): bool
    {
        return $user->can('collections.view') && $user->can('collections.register_payment');
    }
}

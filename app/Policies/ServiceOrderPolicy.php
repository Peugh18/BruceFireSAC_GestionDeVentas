<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_orders.view');
    }

    public function view(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->can('service_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_orders.create');
    }

    public function assign(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->can('service_orders.assign') && $serviceOrder->estado !== 'cerrado';
    }

    public function transition(User $user, ServiceOrder $serviceOrder, string $status): bool
    {
        return in_array($status, $serviceOrder->allowedTransitions(), true)
            && $user->can(ServiceOrder::transitionPermission($status));
    }
}

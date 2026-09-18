<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('certificates.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.view');
    }

    /**
     * Determine whether the user can issue certificates.
     */
    public function create(User $user): bool
    {
        return $user->can('certificates.issue');
    }

    /**
     * Determine whether the user can change the certificate status.
     */
    public function updateStatus(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.manage');
    }
}

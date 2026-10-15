<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function view(?User $user, Certificate $certificate): bool
    {
        return true;
    }
}

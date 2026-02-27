<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function manageCrisisMode(User $user, Brand $brand): bool
    {
        return $user->hasPrivilege('brand.manage');
    }
}

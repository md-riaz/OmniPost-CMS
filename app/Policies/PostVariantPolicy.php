<?php

namespace App\Policies;

use App\Models\PostVariant;
use App\Models\User;

class PostVariantPolicy
{
    public function publishNow(User $user, PostVariant $variant): bool
    {
        if (! $user->hasPrivilege('post.publish')) {
            return false;
        }

        return in_array($variant->status, ['draft', 'scheduled', 'failed'], true);
    }
}

<?php

namespace App\Policies;

use App\Models\ConnectedSocialAccount;
use App\Models\User;

class ConnectedSocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPrivilege('channel.view');
    }

    public function view(User $user, ConnectedSocialAccount $connectedSocialAccount): bool
    {
        return $user->hasPrivilege('channel.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege('channel.connect');
    }

    public function update(User $user, ConnectedSocialAccount $connectedSocialAccount): bool
    {
        return $user->hasPrivilege('channel.manage');
    }

    public function delete(User $user, ConnectedSocialAccount $connectedSocialAccount): bool
    {
        return $user->hasPrivilege('channel.manage');
    }

    public function restore(User $user, ConnectedSocialAccount $connectedSocialAccount): bool
    {
        return $user->hasPrivilege('channel.manage');
    }

    public function forceDelete(User $user, ConnectedSocialAccount $connectedSocialAccount): bool
    {
        return $user->hasPrivilege('channel.manage');
    }
}


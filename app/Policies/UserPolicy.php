<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canPermission('users');
    }

    public function create(User $user): bool
    {
        return $user->canPermission('users');
    }

    public function update(User $user, User $target): bool
    {
        return $user->canPermission('users') && ($user->is_owner || ! $target->is_owner);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->update($user, $target) && $user->isNot($target);
    }
}

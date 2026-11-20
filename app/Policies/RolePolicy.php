<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Roles define what everyone else can do, so only administrators manage them.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function replicate(User $user, Role $role): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_active && $user->is_admin;
    }
}

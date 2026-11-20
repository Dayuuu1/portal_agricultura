<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps each policy ability to a "module.action" permission granted through roles.
 * Actions a module doesn't offer (e.g. creating contact messages) are denied to everyone, administrators included.
 */
abstract class ModulePolicy
{
    abstract protected function module(): string;

    protected function allows(User $user, string $action): bool
    {
        return in_array($action, Permissions::actionsFor($this->module()), true)
            && $user->hasPermission($this->module().'.'.$action);
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permissions::VIEW);
    }

    public function view(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permissions::CREATE);
    }

    public function update(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function delete(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function replicate(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::CREATE);
    }

    public function restore(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, Permissions::UPDATE);
    }

    public function forceDelete(User $user, ?Model $record = null): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, Permissions::DELETE);
    }
}

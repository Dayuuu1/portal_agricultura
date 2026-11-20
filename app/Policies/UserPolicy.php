<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'usuarios';
    }

    /**
     * Only administrators may modify other administrators.
     */
    public function update(User $user, ?Model $record = null): bool
    {
        return parent::update($user, $record) && $this->canHandle($user, $record);
    }

    public function delete(User $user, ?Model $record = null): bool
    {
        return parent::delete($user, $record)
            && $this->canHandle($user, $record)
            && ! ($record instanceof User && $user->is($record));
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, ?Model $record = null): bool
    {
        return $this->delete($user, $record);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Non-administrators may only manage accounts whose roles they fully hold themselves,
     * otherwise resetting someone's password would hand them extra permissions.
     */
    private function canHandle(User $user, ?Model $record): bool
    {
        if ($user->is_admin || ! $record instanceof User) {
            return true;
        }

        return ! $record->is_admin && $record->roles->every(fn (Role $role): bool => $role->isCoveredBy($user));
    }
}

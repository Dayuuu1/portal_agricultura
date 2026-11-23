<?php

namespace App\Policies;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrganizationalUnitPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'unidades';
    }

    /**
     * A unit that documents, job postings, complaints, procedures or other units point to is hidden from the
     * portal instead: deleting it would leave all of them without an office.
     */
    public function delete(User $user, ?Model $record = null): bool
    {
        return parent::delete($user, $record)
            && ! ($record instanceof OrganizationalUnit && $record->hasLinkedRecords());
    }

    public function forceDelete(User $user, ?Model $record = null): bool
    {
        return $this->delete($user, $record);
    }
}

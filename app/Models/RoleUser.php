<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Role assignments, logged so the audit trail shows who granted or removed access.
 */
class RoleUser extends Pivot
{
    protected $table = 'role_user';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::created(fn (RoleUser $assignment) => $assignment->log('role_assigned'));
        static::deleted(fn (RoleUser $assignment) => $assignment->log('role_removed'));
    }

    private function log(string $event): void
    {
        $user = User::query()->find($this->getAttribute('user_id'));
        $roleName = Role::query()->whereKey($this->getAttribute('role_id'))->value('name') ?? '#'.$this->getAttribute('role_id');

        ActivityLog::record($event, $user, ['rol' => ['old' => null, 'new' => $roleName]], label: ($user?->email ?? 'usuario').' · '.$roleName);
    }
}

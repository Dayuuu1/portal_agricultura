<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Content that goes live on the public site: once published, only users with
 * the "publish" permission may change or remove it.
 */
abstract class PublishablePolicy extends ModulePolicy
{
    public function publish(User $user): bool
    {
        return $this->allows($user, Permissions::PUBLISH);
    }

    public function update(User $user, ?Model $record = null): bool
    {
        return parent::update($user, $record) && $this->canTouch($user, $record);
    }

    public function delete(User $user, ?Model $record = null): bool
    {
        return parent::delete($user, $record) && $this->canTouch($user, $record);
    }

    protected function canTouch(User $user, ?Model $record): bool
    {
        return $record === null || ! $record->getAttribute('is_published') || $this->publish($user);
    }
}

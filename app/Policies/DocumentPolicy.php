<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DocumentPolicy extends PublishablePolicy
{
    protected function module(): string
    {
        return 'documentos';
    }

    public function view(User $user, ?Model $record = null): bool
    {
        return parent::view($user, $record) && $this->inAllowedType($user, $record);
    }

    public function update(User $user, ?Model $record = null): bool
    {
        return parent::update($user, $record) && $this->inAllowedType($user, $record);
    }

    public function delete(User $user, ?Model $record = null): bool
    {
        return parent::delete($user, $record) && $this->inAllowedType($user, $record);
    }

    /**
     * Roles may restrict which document types a user handles.
     */
    private function inAllowedType(User $user, ?Model $record): bool
    {
        return $record === null || $user->canUseDocumentType($record->getAttribute('document_type_id'));
    }
}

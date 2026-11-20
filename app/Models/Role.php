<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\Permissions;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'permissions', 'document_type_ids'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::saving(function (Role $role): void {
            $role->permissions = Permissions::sanitize($role->permissions);
            $role->document_type_ids = array_values(array_map('intval', array_filter($role->document_type_ids ?? []))) ?: null;
        });
    }

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'document_type_ids' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(RoleUser::class);
    }

    public function grants(string $module, string $action): bool
    {
        return in_array($action, $this->permissions[$module] ?? [], true);
    }

    /**
     * Whether every permission of this role is also held by the given user (prevents privilege escalation).
     */
    public function isCoveredBy(User $user): bool
    {
        foreach ($this->permissions ?? [] as $module => $actions) {
            foreach ($actions as $action) {
                if (! $user->hasPermission("{$module}.{$action}")) {
                    return false;
                }
            }
        }

        $allowedTypes = $user->allowedDocumentTypeIds();

        return $allowedTypes === null
            || ! $this->grants('documentos', Permissions::VIEW)
            || ($this->document_type_ids !== null && array_diff($this->document_type_ids, $allowedTypes) === []);
    }
}

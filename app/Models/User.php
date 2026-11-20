<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsActivity;
use App\Support\Permissions;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'position', 'email', 'password', 'is_admin', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, LogsActivity, Notifiable;

    /**
     * Mirrors the column defaults so unsaved and freshly created users behave consistently.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
        'is_active' => true,
    ];

    /** @var array<string, list<string>>|null */
    private ?array $resolvedPermissions = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->using(RoleUser::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ($this->is_admin || $this->permissionMap() !== []);
    }

    /**
     * @param  string  $permission  "module.action", e.g. "documentos.publish".
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_admin) {
            return true;
        }

        [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);

        return in_array($action, $this->permissionMap()[$module] ?? [], true);
    }

    /**
     * Union of the permissions granted by all the user's roles.
     *
     * @return array<string, list<string>>
     */
    public function permissionMap(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        $map = [];

        foreach ($this->roles as $role) {
            foreach ($role->permissions ?? [] as $module => $actions) {
                $map[$module] = array_values(array_unique([...($map[$module] ?? []), ...$actions]));
            }
        }

        return $this->resolvedPermissions = $map;
    }

    /**
     * Document types the user may work with; null means every type.
     *
     * @return list<int>|null
     */
    public function allowedDocumentTypeIds(): ?array
    {
        if ($this->is_admin) {
            return null;
        }

        $roles = $this->roles->filter(fn (Role $role): bool => $role->grants('documentos', Permissions::VIEW));

        if ($roles->isEmpty() || $roles->contains(fn (Role $role): bool => $role->document_type_ids === null)) {
            return null;
        }

        return $roles->flatMap(fn (Role $role): array => $role->document_type_ids)->unique()->values()->all();
    }

    public function canUseDocumentType(int|string|null $documentTypeId): bool
    {
        $allowed = $this->allowedDocumentTypeIds();

        return $allowed === null || in_array((int) $documentTypeId, $allowed, true);
    }

    public function refreshPermissions(): void
    {
        $this->resolvedPermissions = null;
        $this->unsetRelation('roles');
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcluded(): array
    {
        return ['password', 'remember_token', 'last_login_at', 'app_authentication_secret', 'app_authentication_recovery_codes'];
    }
}

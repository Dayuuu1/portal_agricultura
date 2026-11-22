<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'event', 'subject_type', 'subject_id', 'subject_label', 'changes', 'ip_address', 'created_at'])]
#[WithoutTimestamps]
class ActivityLog extends Model
{
    public const EVENT_LABELS = [
        'created' => 'Creó',
        'updated' => 'Modificó',
        'deleted' => 'Eliminó',
        'login' => 'Inició sesión',
        'login_failed' => 'Intento fallido de acceso',
        'logout' => 'Cerró sesión',
        'role_assigned' => 'Asignó rol',
        'role_removed' => 'Quitó rol',
    ];

    public const SUBJECT_LABELS = [
        Post::class => 'Noticia',
        Category::class => 'Categoría',
        Page::class => 'Página',
        Slide::class => 'Banner',
        Document::class => 'Documento',
        DocumentType::class => 'Tipo de documento',
        JobPosting::class => 'Convocatoria',
        JobPostingFile::class => 'Archivo de convocatoria',
        OrganizationalUnit::class => 'Unidad orgánica',
        Agency::class => 'Agencia agraria',
        Link::class => 'Enlace',
        Setting::class => 'Dato del portal',
        ContactMessage::class => 'Mensaje',
        Complaint::class => 'Reclamo',
        User::class => 'Usuario',
        Role::class => 'Rol',
        Redirect::class => 'Redirección',
    ];

    private static bool $enabled = true;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Runs the callback without recording activity (e.g. bulk imports).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutLogging(callable $callback): mixed
    {
        $previous = self::$enabled;
        self::$enabled = false;

        try {
            return $callback();
        } finally {
            self::$enabled = $previous;
        }
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(string $event, ?Model $subject = null, ?array $changes = null, ?string $label = null, ?int $userId = null): void
    {
        if (! self::$enabled) {
            return;
        }

        static::query()->create([
            // Always the panel user: on Mesa de Partes requests the default guard is the citizen's.
            'user_id' => $userId ?? auth()->guard('web')->id(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $label,
            'changes' => $changes ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function eventLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::EVENT_LABELS[$this->event] ?? $this->event);
    }

    protected function subjectTypeLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->subject_type ? (self::SUBJECT_LABELS[$this->subject_type] ?? class_basename($this->subject_type)) : null);
    }
}

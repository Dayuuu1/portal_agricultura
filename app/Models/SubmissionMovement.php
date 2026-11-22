<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of the route of an expediente («hoja de ruta»). Internal steps are hidden from the citizen.
 */
#[Fillable(['submission_id', 'action', 'from_status', 'to_status', 'unit_id', 'user_id', 'note', 'is_public', 'created_at'])]
class SubmissionMovement extends Model
{
    public const UPDATED_AT = null;

    /**
     * What each action means on the route.
     */
    public const ACTIONS = [
        'recibido' => 'Documento recibido',
        'observado' => 'Documento observado',
        'subsanado' => 'Observación subsanada',
        'derivado' => 'Derivado a una oficina',
        'en-atencion' => 'En atención',
        'atendido' => 'Respuesta emitida',
        'no-presentado' => 'Declarado no presentado',
        'archivado' => 'Archivado',
        'nota' => 'Nota interna',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SubmissionStatus::class,
            'to_status' => SubmissionStatus::class,
            'is_public' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}

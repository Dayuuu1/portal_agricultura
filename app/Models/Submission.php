<?php

namespace App\Models;

use App\Enums\SubmissionFileKind;
use App\Enums\SubmissionStatus;
use App\Models\Concerns\LogsActivity;
use App\Support\BusinessDays;
use App\Support\MesaPartes\MesaPartesConfig;
use Carbon\CarbonInterface;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A document filed through the Mesa de Partes Virtual («expediente»). It is an official record: it is attended,
 * archived or declared not filed, but never deleted.
 */
#[Fillable([
    'code', 'citizen_id', 'procedure_type_id', 'subject', 'description', 'related_code', 'target_unit_id', 'current_unit_id',
    'status', 'submitted_at', 'filed_on', 'due_on', 'observation', 'observed_at', 'cure_deadline', 'cured_at',
    'response', 'answered_at', 'closed_at', 'folios', 'ip_address',
])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => 'recibido',
        'folios' => 0,
    ];

    /**
     * Correlative number per year without gaps, e.g. "MPV-2026-000015" (the prefix is configurable).
     */
    public static function nextCode(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $prefix = MesaPartesConfig::codePrefix()."-{$year}-";
            $last = static::query()->where('code', 'like', $prefix.'%')->lockForUpdate()->max('code');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'filed_on' => 'date',
            'due_on' => 'date',
            'observed_at' => 'datetime',
            'cure_deadline' => 'date',
            'cured_at' => 'datetime',
            'answered_at' => 'datetime',
            'closed_at' => 'datetime',
            'folios' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /**
     * @return BelongsTo<ProcedureType, $this>
     */
    public function procedureType(): BelongsTo
    {
        return $this->belongsTo(ProcedureType::class);
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'target_unit_id');
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'current_unit_id');
    }

    /**
     * @return HasMany<SubmissionFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class)->orderBy('id');
    }

    /**
     * Route of the file, oldest first.
     *
     * @return HasMany<SubmissionMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(SubmissionMovement::class)->orderBy('created_at')->orderBy('id');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNotIn('status', SubmissionStatus::closed());
    }

    #[Scope]
    protected function toReview(Builder $query): void
    {
        $query->whereIn('status', SubmissionStatus::toReview());
    }

    /**
     * Observations whose term to complete them has passed: they wait for the mesa de partes to close them.
     */
    #[Scope]
    protected function expiredObservations(Builder $query): void
    {
        $query->where('status', SubmissionStatus::Observado)->whereDate('cure_deadline', '<', today());
    }

    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereIn('status', [SubmissionStatus::Derivado, SubmissionStatus::EnAtencion])->whereDate('due_on', '<', today());
    }

    /**
     * @return Collection<int, SubmissionFile>
     */
    public function filesOfKind(SubmissionFileKind ...$kinds): Collection
    {
        return $this->files->filter(fn (SubmissionFile $file): bool => in_array($file->kind, $kinds, true))->values();
    }

    /**
     * Working days left before the term that matters now: the one to complete the observation while observed,
     * otherwise the attention term. Null when no term applies.
     */
    public function workingDaysLeft(): ?int
    {
        $deadline = $this->currentDeadline();

        return $deadline ? BusinessDays::between(today(), $deadline) : null;
    }

    public function currentDeadline(): ?CarbonInterface
    {
        return match (true) {
            $this->status === SubmissionStatus::Observado => $this->cure_deadline,
            $this->status->isOpen() => $this->due_on,
            default => null,
        };
    }

    public function canBeCured(): bool
    {
        return $this->status === SubmissionStatus::Observado && $this->cure_deadline?->endOfDay()->isFuture();
    }

    public function activityLogLabel(): string
    {
        return $this->code;
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcluded(): array
    {
        return ['ip_address'];
    }

    /**
     * What the citizen and the staff wrote stays in the expediente, readable only with access to the Mesa de Partes.
     *
     * @return list<string>
     */
    protected function activityLogMasked(): array
    {
        return ['subject', 'description', 'observation', 'response'];
    }
}

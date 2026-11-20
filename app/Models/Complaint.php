<?php

namespace App\Models;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Enums\IdentityDocumentType;
use App\Models\Concerns\LogsActivity;
use App\Support\BusinessDays;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A claim registered in the Libro de Reclamaciones (DS N° 007-2020-PCM).
 * Claims are official records: they are answered or archived, never deleted.
 */
#[Fillable([
    'full_name', 'document_type', 'document_number', 'address', 'phone', 'email',
    'is_minor', 'guardian_name', 'guardian_document_number',
    'incident_date', 'agency_id', 'organizational_unit_id', 'reason', 'description', 'request', 'attachments', 'notify_by_email',
    'status', 'response', 'responded_at', 'responded_by', 'internal_notes', 'ip_address',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory, LogsActivity;

    /**
     * Maximum time to answer, in working days counted from the day after registration (art. 14).
     */
    public const RESPONSE_DAYS = 30;

    protected $attributes = [
        'status' => 'registrado',
        'is_minor' => false,
        'notify_by_email' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint): void {
            $registeredAt = $complaint->created_at ?? now();
            $complaint->code ??= static::nextCode((int) $registeredAt->format('Y'));
            $complaint->due_on ??= BusinessDays::after($registeredAt, self::RESPONSE_DAYS);
        });
    }

    /**
     * Correlative number per year, e.g. "LR-2026-000015".
     */
    public static function nextCode(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $prefix = "LR-{$year}-";
            $last = static::query()->where('code', 'like', $prefix.'%')->lockForUpdate()->max('code');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }

    protected function casts(): array
    {
        return [
            'document_type' => IdentityDocumentType::class,
            'reason' => ComplaintReason::class,
            'status' => ComplaintStatus::class,
            'is_minor' => 'boolean',
            'notify_by_email' => 'boolean',
            'attachments' => 'array',
            'incident_date' => 'date',
            'due_on' => 'date',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', ComplaintStatus::open());
    }

    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->open()->whereDate('due_on', '<', today());
    }

    public function placeLabel(): string
    {
        return $this->agency?->name ?? 'Sede central';
    }

    /**
     * Working days left to answer (negative when the deadline has passed).
     */
    public function workingDaysLeft(): int
    {
        return BusinessDays::between(today(), $this->due_on);
    }

    public function canBeNotifiedByEmail(): bool
    {
        return $this->notify_by_email && filled($this->email);
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
        return ['full_name', 'document_number', 'address', 'phone', 'email', 'guardian_name', 'guardian_document_number', 'description', 'request', 'attachments', 'ip_address'];
    }
}

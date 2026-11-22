<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use App\Support\MesaPartes\MesaPartesConfig;
use Database\Factories\ProcedureTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kind of document the Mesa de Partes receives (general request, access to public information, TUPA procedures…),
 * with its own term and requirements.
 */
#[Fillable(['name', 'slug', 'description', 'requirements', 'response_days', 'fee', 'requires_payment_proof', 'default_unit_id', 'is_active', 'sort_order'])]
class ProcedureType extends Model
{
    /** @use HasFactory<ProcedureTypeFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'response_days' => 'integer',
            'fee' => 'decimal:2',
            'requires_payment_proof' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'default_unit_id');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Working days to attend it; null when the type has no term of its own (e.g. documents for a file in progress).
     */
    public function attentionDays(): ?int
    {
        return $this->response_days === 0 ? null : ($this->response_days ?? MesaPartesConfig::responseDays());
    }

    /**
     * @return list<string>
     */
    public function requirementList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->requirements) ?: [])));
    }
}

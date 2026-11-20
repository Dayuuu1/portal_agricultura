<?php

namespace App\Models;

use App\Enums\JobPostingStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organizational_unit_id', 'code', 'title', 'slug', 'modality', 'positions_count',
    'description', 'status', 'starts_at', 'ends_at', 'is_published',
])]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function slugSource(): string
    {
        return 'code';
    }

    protected function casts(): array
    {
        return [
            'status' => JobPostingStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * @return HasMany<JobPostingFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(JobPostingFile::class)->orderBy('sort_order');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }
}

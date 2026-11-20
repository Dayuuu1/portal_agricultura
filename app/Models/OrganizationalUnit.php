<?php

namespace App\Models;

use App\Enums\UnitType;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\OrganizationalUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'parent_id', 'name', 'slug', 'acronym', 'type', 'description', 'functions',
    'head_name', 'head_title', 'head_photo', 'email', 'phone', 'sort_order', 'is_active',
])]
class OrganizationalUnit extends Model
{
    /** @use HasFactory<OrganizationalUnitFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OrganizationalUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function headPhotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->head_photo
            ? Storage::disk('public')->url($this->head_photo)
            : null);
    }
}

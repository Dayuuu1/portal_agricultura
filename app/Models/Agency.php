<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'slug', 'province', 'district', 'address', 'phone', 'email', 'head_name',
    'schedule', 'description', 'services', 'latitude', 'longitude', 'photo', 'sort_order', 'is_active',
])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo
            ? Storage::disk('public')->url($this->photo)
            : null);
    }

    protected function mapUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->latitude && $this->longitude
            ? "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}#map=16/{$this->latitude}/{$this->longitude}"
            : null);
    }
}

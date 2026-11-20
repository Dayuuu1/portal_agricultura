<?php

namespace App\Models;

use App\Enums\LinkGroup;
use App\Models\Concerns\LogsActivity;
use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'url', 'logo', 'description', 'group', 'show_in_carousel', 'sort_order', 'is_active'])]
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'group' => LinkGroup::class,
            'show_in_carousel' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Active links shown in the home page carousel, in the order set in the panel.
     */
    #[Scope]
    protected function inCarousel(Builder $query): void
    {
        $query->active()->where('show_in_carousel', true)->orderBy('sort_order')->orderBy('name');
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo
            ? Storage::disk('public')->url($this->logo)
            : null);
    }
}

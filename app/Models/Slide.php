<?php

namespace App\Models;

use App\Enums\BannerOverlay;
use App\Models\Concerns\LogsActivity;
use App\Rules\SafeLink;
use Database\Factories\SlideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title', 'eyebrow', 'subtitle', 'image', 'background_image', 'overlay',
    'button_text', 'button_url', 'sort_order', 'starts_at', 'ends_at', 'is_active',
])]
class Slide extends Model
{
    /** @use HasFactory<SlideFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'overlay' => 'medium',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'overlay' => BannerOverlay::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Enabled banners within their publication window.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * @return array{label: string, color: string}
     */
    public function visibility(): array
    {
        return match (true) {
            ! $this->is_active => ['label' => 'Desactivado', 'color' => 'gray'],
            $this->starts_at?->isFuture() => ['label' => 'Programado', 'color' => 'info'],
            $this->ends_at?->isPast() => ['label' => 'Finalizado', 'color' => 'warning'],
            default => ['label' => 'Visible', 'color' => 'success'],
        };
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image
            ? Storage::disk('public')->url($this->image)
            : null);
    }

    protected function backgroundUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->background_image
            ? Storage::disk('public')->url($this->background_image)
            : null);
    }

    /**
     * Address of the button, or null when there is none or it is not a portal path or web address
     * (values saved before the form checked them are never printed in an href).
     */
    public function buttonLink(): ?string
    {
        return SafeLink::isSafe($this->button_url) ? $this->button_url : null;
    }
}

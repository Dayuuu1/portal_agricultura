<?php

namespace App\Models;

use App\Enums\AnnouncementFrequency;
use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLayout;
use App\Enums\AnnouncementPlacement;
use App\Models\Concerns\LogsActivity;
use App\Rules\SafeLink;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Window that opens over the portal when a visitor arrives: notices, events, campaigns or a highlighted news post.
 * When it highlights a post, whatever the announcement leaves empty (text, photo, button) comes from the post.
 */
#[Fillable([
    'kind', 'title', 'body', 'image', 'layout', 'post_id', 'button_text', 'button_url',
    'placement', 'frequency', 'sort_order', 'starts_at', 'ends_at', 'is_active',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'kind' => 'notice',
        'layout' => 'card',
        'placement' => 'home',
        'frequency' => 'once',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'kind' => AnnouncementKind::class,
            'layout' => AnnouncementLayout::class,
            'placement' => AnnouncementPlacement::class,
            'frequency' => AnnouncementFrequency::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return HasMany<AnnouncementStat, $this>
     */
    public function stats(): HasMany
    {
        return $this->hasMany(AnnouncementStat::class);
    }

    /**
     * Enabled announcements within their publication window. One that highlights a news post
     * waits until the post is published, so the window never points to a draft.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn (Builder $query) => $query->whereNull('post_id')->orWhereHas('post', fn (Builder $query) => $query->published()));
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
            $this->post_id !== null && ! ($this->post?->is_published && $this->post->published_at?->isPast()) => ['label' => 'Noticia sin publicar', 'color' => 'warning'],
            default => ['label' => 'Visible', 'color' => 'success'],
        };
    }

    /**
     * Text of the window: its own, or the summary of the highlighted post.
     */
    protected function text(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->body) ? $this->body : $this->post?->summary);
    }

    /**
     * Its own image, or the cover of the highlighted post.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->image ?: $this->post?->cover_image;

            return $path ? Storage::disk('public')->url($path) : null;
        });
    }

    /**
     * Address of the button: the one typed in the panel (only if it is a portal path or web address),
     * otherwise the highlighted post. Null when there is nowhere to go.
     */
    public function link(): ?string
    {
        if (SafeLink::isSafe($this->button_url)) {
            return $this->button_url;
        }

        return $this->post ? route('posts.show', $this->post) : null;
    }

    public function buttonLabel(): string
    {
        return $this->button_text ?: ($this->post && blank($this->button_url) ? 'Leer la noticia' : 'Ver más');
    }

    /**
     * Changes whenever the announcement is edited, so visitors who already closed it see the new version.
     */
    public function version(): string
    {
        return (string) ($this->updated_at?->getTimestamp() ?? 0);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'user_id', 'title', 'slug', 'excerpt', 'body', 'cover_image',
    'gallery', 'is_featured', 'is_published', 'published_at',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_image
            ? Storage::disk('public')->url($this->cover_image)
            : null);
    }

    protected function summary(): Attribute
    {
        return Attribute::get(fn (): string => $this->excerpt
            ?: Str::limit(trim(strip_tags((string) $this->body)), 180));
    }

    /**
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        return collect($this->gallery ?? [])
            ->map(fn (string $path): string => Storage::disk('public')->url($path))
            ->values()
            ->all();
    }
}

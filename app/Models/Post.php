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
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function coverPhoto(): ?array
    {
        return $this->cover_image ? $this->photo($this->cover_image) : null;
    }

    /**
     * Gallery photos, leaving out the cover when it was also added to the gallery.
     *
     * @return list<array{url: string, width: int|null, height: int|null}>
     */
    public function galleryPhotos(): array
    {
        return collect($this->gallery ?? [])
            ->reject(fn (string $path): bool => $path === $this->cover_image)
            ->map(fn (string $path): array => $this->photo($path))
            ->values()
            ->all();
    }

    /**
     * Public URL and displayed size of a stored photo, so the photo viewer opens it with its real proportions.
     * Browsers rotate photos by their EXIF orientation, so portrait camera shots swap width and height.
     *
     * @return array{url: string, width: int|null, height: int|null}
     */
    private function photo(string $path): array
    {
        $disk = Storage::disk('public');
        $file = $disk->path($path);
        $size = is_file($file) ? rescue(fn (): array|false => getimagesize($file), false, report: false) : false;
        [$width, $height] = $size ? [$size[0], $size[1]] : [null, null];

        if ($size && $size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = rescue(fn (): mixed => (exif_read_data($file) ?: [])['Orientation'] ?? 1, 1, report: false);

            if (in_array($orientation, [5, 6, 7, 8], true)) {
                [$width, $height] = [$height, $width];
            }
        }

        return ['url' => $disk->url($path), 'width' => $width, 'height' => $height];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'slug', 'excerpt', 'body', 'is_published', 'show_in_menu'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    /**
     * Pages linked from the layout and routes; their slugs must not change.
     */
    public const SYSTEM_SLUGS = ['nosotros', 'codigo-de-etica', 'politica-de-privacidad'];

    public function isSystemPage(): bool
    {
        return in_array($this->getOriginal('slug'), self::SYSTEM_SLUGS, true);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }

    /**
     * Documents of the repository offered for download on the page (approved text, resolutions…).
     *
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class)->withPivot('sort_order');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Published pages listed under «Institucional» in the main menu.
     */
    #[Scope]
    protected function inMenu(Builder $query): void
    {
        $query->where('is_published', true)->where('show_in_menu', true);
    }
}

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

#[Fillable(['title', 'slug', 'excerpt', 'body', 'is_published'])]
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
        ];
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }
}

<?php

namespace App\Models;

use App\Enums\SiteEventType;
use Database\Factories\SiteEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A search, a document download or a link to another site, reported from a page of the public site.
 */
#[Fillable(['page_view_id', 'type', 'label', 'context', 'value', 'date', 'created_at'])]
class SiteEvent extends Model
{
    /** @use HasFactory<SiteEventFactory> */
    use HasFactory, MassPrunable;

    public const UPDATED_AT = null;

    /**
     * "date" stays a plain Y-m-d string, like in PageView.
     */
    protected function casts(): array
    {
        return [
            'type' => SiteEventType::class,
            'value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PageView, $this>
     */
    public function pageView(): BelongsTo
    {
        return $this->belongsTo(PageView::class);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(PageView::RETENTION_MONTHS));
    }
}

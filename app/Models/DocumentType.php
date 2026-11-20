<?php

namespace App\Models;

use App\Enums\DocumentSection;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'section', 'group_by_year', 'description', 'sort_order'])]
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'section' => DocumentSection::class,
            'group_by_year' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}

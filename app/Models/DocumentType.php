<?php

namespace App\Models;

use App\Enums\DocumentSection;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Statistics published every month (crop production, prices, bulletins…), whose titles name the month they report on.
     */
    public function isMonthlyStatistics(): bool
    {
        return $this->isAgrarianInformation() && $this->group_by_year;
    }

    public function isAgrarianInformation(): bool
    {
        return $this->section === DocumentSection::InformacionAgraria;
    }

    /**
     * Public page of the type. Agrarian statistics live under «Información agraria»; everything else under «Documentos».
     *
     * @param  array<string, mixed>  $query
     */
    public function publicUrl(array $query = [], bool $absolute = true): string
    {
        return $this->isAgrarianInformation()
            ? route('agrarian-information.type', ['documentType' => $this->slug, ...$query], $absolute)
            : route('documents.index', ['tipo' => $this->slug, ...$query], $absolute);
    }

    #[Scope]
    protected function monthlyStatistics(Builder $query): void
    {
        $query->where('section', DocumentSection::InformacionAgraria)->where('group_by_year', true);
    }
}

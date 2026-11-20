<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use App\Support\WordPress\TextNormalizer;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

#[Fillable([
    'document_type_id', 'organizational_unit_id', 'title', 'slug', 'code', 'description',
    'issued_at', 'period_year', 'file_path', 'file_name', 'file_size', 'external_url', 'is_published',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            if ($document->isDirty('file_path')) {
                $disk = Storage::disk('public');
                $document->file_size = $document->file_path && $disk->exists($document->file_path)
                    ? $disk->size($document->file_path)
                    : null;
            }

            if (blank($document->period_year)) {
                $document->period_year = static::inferPeriodYear((string) $document->title) ?? $document->issued_at?->year;
            }
        });
    }

    /**
     * Reference year of the information, taken from titles such as "Boletín diciembre 2025".
     */
    public static function inferPeriodYear(string $title): ?int
    {
        preg_match_all('/(?<!\d)(19[89]\d|20\d{2})(?!\d)/', $title, $matches);

        foreach ($matches[1] as $year) {
            if ((int) $year <= now()->year + 1) {
                return (int) $year;
            }
        }

        return null;
    }

    /**
     * Readable title from an uploaded file name ("BOLETIN_MAYO-2026.pdf" → "Boletín mayo 2026").
     */
    public static function titleFromFileName(string $fileName): string
    {
        $name = (string) preg_replace('/[\s_\-]+/u', ' ', pathinfo($fileName, PATHINFO_FILENAME));

        return Str::ucfirst((new TextNormalizer)->title($name)) ?: 'Documento';
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'period_year' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Limits the query to the document types the user's roles allow.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $allowedTypeIds = $user->allowedDocumentTypeIds();

        if ($allowedTypeIds !== null) {
            $query->whereIn('document_type_id', $allowedTypeIds);
        }
    }

    public function hasFile(): bool
    {
        return filled($this->file_path) || filled($this->external_url);
    }

    protected function fileExtension(): Attribute
    {
        return Attribute::get(function (): ?string {
            $source = $this->file_name ?: $this->file_path;

            return $source ? Str::upper(pathinfo($source, PATHINFO_EXTENSION)) : null;
        });
    }

    protected function humanFileSize(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->file_size
            ? Number::fileSize($this->file_size, maxPrecision: 1)
            : null);
    }
}

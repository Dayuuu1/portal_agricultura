<?php

namespace App\Models;

use App\Enums\SubmissionFileKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * File of an expediente, kept on the private disk with its SHA-256 fingerprint (printed on the cargo).
 */
#[Fillable(['submission_id', 'kind', 'path', 'original_name', 'mime', 'size', 'pages', 'sha256', 'uploaded_by'])]
class SubmissionFile extends Model
{
    public const DISK = 'local';

    protected function casts(): array
    {
        return [
            'kind' => SubmissionFileKind::class,
            'size' => 'integer',
            'pages' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function extension(): string
    {
        return Str::upper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, maxPrecision: 1);
    }
}

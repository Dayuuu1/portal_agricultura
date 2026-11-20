<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\JobPostingFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['job_posting_id', 'label', 'file_path', 'published_at', 'sort_order'])]
class JobPostingFile extends Model
{
    /** @use HasFactory<JobPostingFileFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->file_path
            ? Storage::disk('public')->url($this->file_path)
            : null);
    }
}

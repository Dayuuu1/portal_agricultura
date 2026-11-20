<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Records who created, modified or deleted the model, for the admin audit trail.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => ActivityLog::record('created', $model, label: $model->activityLogLabel()));

        static::updated(function (Model $model): void {
            $changes = $model->activityLogChanges();

            if ($changes !== []) {
                ActivityLog::record('updated', $model, $changes, $model->activityLogLabel());
            }
        });

        static::deleted(fn (Model $model) => ActivityLog::record('deleted', $model, label: $model->activityLogLabel()));
    }

    public function activityLogLabel(): string
    {
        $label = $this->getAttribute('title') ?? $this->getAttribute('name') ?? $this->getAttribute('code')
            ?? $this->getAttribute('label') ?? $this->getAttribute('subject') ?? '#'.$this->getKey();

        return Str::limit((string) $label, 180);
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcluded(): array
    {
        return [];
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function activityLogChanges(): array
    {
        $ignored = [...$this->activityLogExcluded(), 'updated_at', 'created_at', 'views', 'downloads'];
        $changes = [];

        foreach ($this->getChanges() as $attribute => $value) {
            if (in_array($attribute, $ignored, true)) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $this->activityLogValue($this->getOriginal($attribute)),
                'new' => $this->activityLogValue($this->getAttribute($attribute)),
            ];
        }

        return $changes;
    }

    private function activityLogValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i'),
            is_string($value) => Str::limit(trim(strip_tags($value)), 200),
            is_iterable($value) => collect($value)->map(fn ($item) => $item instanceof BackedEnum ? $item->value : $item)->all(),
            default => $value,
        };
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#[Fillable(['old_path', 'new_path'])]
class Redirect extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Canonical form used to store and look up old URLs: "/ruta" or "/?ddownload=ID".
     */
    public static function normalizePath(string $path): string
    {
        $path = rawurldecode(parse_url($path, PHP_URL_PATH) ?? $path);

        return '/'.Str::lower(trim($path, '/'));
    }

    public static function downloadPath(int|string $wordpressId): string
    {
        return '/?ddownload='.(int) $wordpressId;
    }

    public static function findForRequest(Request $request): ?self
    {
        $candidates = [static::normalizePath($request->path())];

        if ($request->filled('ddownload')) {
            $candidates[] = static::downloadPath($request->integer('ddownload'));
        }

        return static::query()->whereIn('old_path', $candidates)->first();
    }

    public function registerHit(): void
    {
        static::query()->whereKey($this->getKey())->toBase()->update([
            'hits' => $this->hits + 1,
            'last_hit_at' => now(),
        ]);
    }

    public function activityLogLabel(): string
    {
        return $this->old_path.' → '.$this->new_path;
    }
}

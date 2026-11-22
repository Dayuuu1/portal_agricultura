<?php

namespace Tests\Concerns;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Once;

trait InteractsWithMesaPartes
{
    protected function configureMesaPartes(array $values = []): void
    {
        foreach (['mpv_enabled' => '1', ...$values] as $key => $value) {
            Setting::query()->where('key', $key)->firstOrFail()->update(['value' => $value]);
        }

        // Settings are memoized for the request; a test spans several requests.
        Once::flush();
    }

    /**
     * The guard keeps the signed-in model between test requests; a real request reloads it.
     */
    protected function reloadCitizen(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * A small but real PDF with the given number of pages.
     */
    protected function pdf(string $name = 'escrito.pdf', int $pages = 1): UploadedFile
    {
        $body = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Count {$pages} >> endobj\n"
            .str_repeat("3 0 obj << /Type /Page /Parent 2 0 R >> endobj\n", $pages)."%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $body);
    }
}

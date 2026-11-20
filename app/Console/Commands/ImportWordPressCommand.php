<?php

namespace App\Console\Commands;

use App\Support\WordPress\MysqlDumpReader;
use App\Support\WordPress\WordPressImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Throwable;

#[Signature('portal:importar-wordpress
    {sql : Ruta del respaldo .sql de WordPress}
    {--documentos= : Carpeta exportada con los documentos (delightful-downloads)}
    {--imagenes= : Carpeta exportada con las imágenes (wp-content/uploads)}
    {--simular : Analiza y muestra el reporte sin guardar nada}')]
#[Description('Migra noticias, documentos, convocatorias y páginas del portal WordPress anterior')]
class ImportWordPressCommand extends Command
{
    public function handle(): int
    {
        $sql = (string) $this->argument('sql');
        $documents = (string) $this->option('documentos');
        $images = $this->option('imagenes') ?: null;
        $dryRun = (bool) $this->option('simular');

        if (! is_file($sql)) {
            $this->components->error("No se encontró el respaldo SQL: {$sql}");

            return self::FAILURE;
        }

        if ($documents === '' || ! is_dir($documents)) {
            $this->components->error('Indique la carpeta de documentos con --documentos=');

            return self::FAILURE;
        }

        if ($images !== null && ! is_dir($images)) {
            $this->components->error("No existe la carpeta de imágenes: {$images}");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->components->warn('Modo simulación: no se guardará ningún cambio.');
        }

        try {
            $report = (new WordPressImporter(
                reader: new MysqlDumpReader($sql),
                documentsPath: $documents,
                imagesPath: $images,
                dryRun: $dryRun,
                progress: fn (string $message) => $this->components->info($message),
            ))->run();
        } catch (Throwable $exception) {
            $this->components->error('La importación falló: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Concepto', 'Cantidad'], collect($report->counters)->map(fn (int $value, string $key): array => [$key, $value])->values()->all());
        $this->components->twoColumnDetail('Archivos copiados', Number::fileSize($report->bytesCopied, precision: 1));

        $reportPath = 'importacion/reporte-'.now()->format('Ymd-His').($dryRun ? '-simulacion' : '').'.md';
        Storage::disk('local')->put($reportPath, $report->toMarkdown());
        $this->components->info('Reporte detallado: '.Storage::disk('local')->path($reportPath));

        return self::SUCCESS;
    }
}

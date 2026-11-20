<?php

namespace App\Support\WordPress;

use Illuminate\Support\Number;

final class ImportReport
{
    /** @var array<string, int> */
    public array $counters = [];

    /** @var array<string, list<string>> */
    public array $lists = [];

    public int $bytesCopied = 0;

    public function __construct(public readonly bool $dryRun) {}

    public function increment(string $counter, int $amount = 1): void
    {
        $this->counters[$counter] = ($this->counters[$counter] ?? 0) + $amount;
    }

    public function add(string $list, string $item): void
    {
        $this->lists[$list][] = $item;
    }

    public function count(string $counter): int
    {
        return $this->counters[$counter] ?? 0;
    }

    public function toMarkdown(): string
    {
        $lines = [
            '# Reporte de importación del portal WordPress',
            '',
            'Fecha: '.now()->format('d/m/Y H:i').($this->dryRun ? ' (simulación, sin cambios)' : ''),
            'Archivos copiados: '.Number::fileSize($this->bytesCopied, precision: 1),
            '',
            '## Resumen',
            '',
            '| Concepto | Cantidad |',
            '| --- | ---: |',
        ];

        foreach ($this->counters as $counter => $value) {
            $lines[] = "| {$counter} | {$value} |";
        }

        foreach ($this->lists as $list => $items) {
            $lines[] = '';
            $lines[] = "## {$list} (".count($items).')';
            $lines[] = '';

            foreach ($items as $item) {
                $lines[] = '- '.$item;
            }
        }

        return implode("\n", $lines)."\n";
    }
}

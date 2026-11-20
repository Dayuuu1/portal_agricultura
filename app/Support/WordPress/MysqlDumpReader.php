<?php

namespace App\Support\WordPress;

use Generator;
use RuntimeException;

/**
 * Streams rows from a mysqldump file (extended INSERT format) without needing a MySQL server.
 */
final class MysqlDumpReader
{
    private const ESCAPES = [
        '0' => "\0",
        'b' => "\x08",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'Z' => "\x1a",
    ];

    public function __construct(private readonly string $path)
    {
        if (! is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo SQL: {$path}");
        }
    }

    /**
     * Detects the WordPress table prefix (e.g. "wp_" or "WqpPqgS0_").
     */
    public function detectPrefix(): string
    {
        $handle = fopen($this->path, 'rb');

        try {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^CREATE TABLE `(.+)posts` \(/', $line, $matches)) {
                    return $matches[1];
                }
            }
        } finally {
            fclose($handle);
        }

        throw new RuntimeException('No se encontró la tabla de entradas de WordPress en el respaldo.');
    }

    /**
     * @return Generator<int, array<string, string|null>>
     */
    public function rows(string $table): Generator
    {
        $handle = fopen($this->path, 'rb');
        $columns = null;
        $createPrefix = "CREATE TABLE `{$table}` (";
        $insertPrefix = "INSERT INTO `{$table}` VALUES ";

        try {
            while (($line = fgets($handle)) !== false) {
                if ($columns === null && str_starts_with($line, $createPrefix)) {
                    $columns = $this->readColumns($handle);

                    continue;
                }

                if (! str_starts_with($line, $insertPrefix)) {
                    continue;
                }

                if ($columns === null) {
                    throw new RuntimeException("La tabla {$table} no tiene definición de columnas en el respaldo.");
                }

                foreach ($this->parseTuples(substr($line, strlen($insertPrefix))) as $values) {
                    yield array_combine($columns, $values);
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @return list<string>
     */
    private function readColumns($handle): array
    {
        $columns = [];

        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\s+`([^`]+)`\s/', $line, $matches)) {
                $columns[] = $matches[1];
            } elseif (str_starts_with(ltrim($line), ')')) {
                break;
            }
        }

        return $columns;
    }

    /**
     * @return Generator<int, list<string|null>>
     */
    private function parseTuples(string $sql): Generator
    {
        $length = strlen($sql);
        $position = 0;

        while (($position = strpos($sql, '(', $position)) !== false) {
            $position++;
            $values = [];

            while ($position < $length) {
                if (substr_compare($sql, "_binary '", $position, 9) === 0) {
                    $position += 8;
                }

                if ($sql[$position] === "'") {
                    $position++;
                    $buffer = '';

                    while (true) {
                        $chunk = strcspn($sql, "\\'", $position);
                        $buffer .= substr($sql, $position, $chunk);
                        $position += $chunk;

                        if ($sql[$position] === '\\') {
                            $escaped = $sql[$position + 1];
                            $buffer .= self::ESCAPES[$escaped] ?? $escaped;
                            $position += 2;
                        } elseif (($sql[$position + 1] ?? '') === "'") {
                            $buffer .= "'";
                            $position += 2;
                        } else {
                            $position++;
                            break;
                        }
                    }

                    $values[] = $buffer;
                } else {
                    $end = strcspn($sql, ',)', $position);
                    $raw = trim(substr($sql, $position, $end));
                    $position += $end;
                    $values[] = strcasecmp($raw, 'NULL') === 0 ? null : $raw;
                }

                if ($sql[$position] === ',') {
                    $position++;

                    continue;
                }

                $position++;
                break;
            }

            yield $values;
        }
    }
}

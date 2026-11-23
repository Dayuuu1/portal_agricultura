<?php

namespace App\Support;

/**
 * Text typed by citizens and shown inside Markdown e-mails. Blade already escapes HTML; this also escapes the
 * Markdown syntax that could turn it into something else: links and images («[Pague aquí](https://…)»), emphasis,
 * code and table cells. The message then shows exactly what the person wrote, never a link sent from the entity.
 */
final class MarkdownText
{
    public static function escape(?string $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]|])/', '\\\\$1', (string) $text);
    }
}

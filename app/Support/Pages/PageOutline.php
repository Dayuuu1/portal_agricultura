<?php

namespace App\Support\Pages;

use Illuminate\Support\Str;

/**
 * Content of an institutional page ready to print: the sanitized HTML with an anchor on each section
 * title (h2 and h3), and the list of those titles for the «En esta página» index.
 */
final readonly class PageOutline
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $headings
     */
    public function __construct(
        public string $html,
        public array $headings,
    ) {}

    public static function fromHtml(?string $html): self
    {
        $html = (string) str($html ?? '')->sanitizeHtml();
        $headings = [];
        $usedIds = [];

        $html = (string) preg_replace_callback('/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', function (array $match) use (&$headings, &$usedIds): string {
            $level = (int) $match[1];
            $text = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($text === '') {
                return $match[0];
            }

            $base = Str::slug($text) ?: 'seccion';
            $id = $base;
            for ($suffix = 2; isset($usedIds[$id]); $suffix++) {
                $id = "{$base}-{$suffix}";
            }
            $usedIds[$id] = true;

            $headings[] = ['id' => $id, 'text' => $text, 'level' => $level];
            $attributes = (string) preg_replace('/\sid="[^"]*"/i', '', $match[2] ?? '');

            return "<h{$level} id=\"{$id}\"{$attributes}>{$match[3]}</h{$level}>";
        }, $html);

        return new self($html, $headings);
    }

    /**
     * The index is only worth showing when the page has at least two sections.
     */
    public function hasIndex(): bool
    {
        return count(array_filter($this->headings, fn (array $heading): bool => $heading['level'] === 2)) >= 2;
    }
}

<?php

namespace App\Support\OrganizationalUnits;

/**
 * The «Funciones» of a unit ready to print. Much of the text came from the former portal as one paragraph per
 * function, each opening with a space; shown that way it reads as loose prose. A text made only of such
 * paragraphs is shown as a list, keeping the fully bold paragraphs as subheadings between the items. Text that
 * already has a list, or numbers or letters its own items («a)», «1.», «-»), is shown as written.
 * Only the presentation changes: the stored text is never rewritten.
 */
final class UnitFunctions
{
    private const PARAGRAPH = '/<p(?:\s[^>]*)?>(.*?)<\/p>/is';

    private const OWN_MARKER = '/^(?:[a-z]\)|\d{1,2}[.)°º]|[-–—•·*])/iu';

    public static function toHtml(?string $html): string
    {
        $html = (string) str($html ?? '')->sanitizeHtml();
        $html = (string) preg_replace('/(<(?:p|li)(?:\s[^>]*)?>)(?:\s|&nbsp;|&#160;|\x{00A0})+/iu', '$1', $html);

        if (preg_match('/<(?:ul|ol)\b/i', $html) === 1 || trim((string) preg_replace(self::PARAGRAPH, '', $html)) !== '') {
            return $html;
        }

        preg_match_all(self::PARAGRAPH, $html, $matches);
        $paragraphs = array_values(array_filter(array_map(trim(...), $matches[1]), fn (string $inner): bool => self::text($inner) !== ''));
        $items = array_filter($paragraphs, fn (string $inner): bool => ! self::isSubheading($inner));

        if (count($items) < 2 || collect($items)->contains(fn (string $inner): bool => preg_match(self::OWN_MARKER, self::text($inner)) === 1)) {
            return $html;
        }

        $output = '';
        $listIsOpen = false;

        foreach ($paragraphs as $inner) {
            if (self::isSubheading($inner)) {
                $output .= ($listIsOpen ? '</ul>' : '')."<p>{$inner}</p>";
                $listIsOpen = false;

                continue;
            }

            $output .= ($listIsOpen ? '' : '<ul>')."<li>{$inner}</li>";
            $listIsOpen = true;
        }

        return $output.($listIsOpen ? '</ul>' : '');
    }

    /**
     * A paragraph written entirely in bold, such as the name of a unit or a section of the text.
     */
    private static function isSubheading(string $inner): bool
    {
        return preg_match('/^<(strong|b)>((?:(?!<\/\1>).)*)<\/\1>$/is', $inner) === 1;
    }

    private static function text(string $inner): string
    {
        return trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\u{00A0}");
    }
}

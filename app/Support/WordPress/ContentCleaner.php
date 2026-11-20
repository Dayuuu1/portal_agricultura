<?php

namespace App\Support\WordPress;

use Closure;

/**
 * Converts WordPress / WPBakery post content into clean HTML for the new portal.
 */
final class ContentCleaner
{
    private const SHORTCODE_NAMES = 'vc_[a-z_]+|ddownload_list|fastgallery[a-z_]*|fastcarousel[a-z_]*|caption|gallery|embed|contact-form-7|video|audio|wpb_[a-z_]+|rev_slider';

    private const BLOCK_TAGS = 'p|ul|ol|li|h[1-6]|table|thead|tbody|tr|td|th|blockquote|figure|hr|pre';

    /**
     * @param  Closure(int): ?array{url: string, title: string}  $resolveDownload
     * @param  Closure(string): ?string  $resolveImageUrl  Old upload URL → new public URL.
     * @return array{html: string, text: string, gallery: list<int>, downloads: list<int>}
     */
    public function clean(string $content, Closure $resolveDownload, Closure $resolveImageUrl, string $legacyHost = 'dramdd.gob.pe'): array
    {
        $gallery = [];
        $downloads = [];
        $html = str_replace(["\r\n", "\r"], "\n", $content);

        $html = (string) preg_replace_callback('/\[(vc_gallery|gallery)[^\]]*?(?:images|ids)="([\d,\s]+)"[^\]]*\]/i', function (array $match) use (&$gallery): string {
            array_push($gallery, ...array_map('intval', array_filter(array_map('trim', explode(',', $match[2])))));

            return '';
        }, $html);

        $html = (string) preg_replace_callback('/\[vc_single_image[^\]]*?image="(\d+)"[^\]]*\]/i', function (array $match) use (&$gallery): string {
            $gallery[] = (int) $match[1];

            return '';
        }, $html);

        $html = (string) preg_replace_callback('/\[vc_video[^\]]*?link="([^"]+)"[^\]]*\]/i', function (array $match): string {
            $url = htmlspecialchars($match[1], ENT_QUOTES);

            return "\n\n<p><a href=\"{$url}\" target=\"_blank\" rel=\"noopener\">Ver video</a></p>\n\n";
        }, $html);

        $html = (string) preg_replace_callback('/\[ddownload\s[^\]]*?id="?(\d+)"?[^\]]*\]/i', function (array $match) use (&$downloads, $resolveDownload): string {
            $id = (int) $match[1];
            $downloads[] = $id;
            $target = $resolveDownload($id);

            if ($target === null) {
                return '';
            }

            $label = htmlspecialchars($target['title'], ENT_QUOTES);

            return "<a href=\"{$target['url']}\">{$label}</a>";
        }, $html);

        $html = (string) preg_replace('/\[\/?(?:'.self::SHORTCODE_NAMES.')(?:\s[^\]]*)?\]/i', "\n", $html);

        $html = (string) preg_replace_callback('/<img\s[^>]*?src="([^"]+)"[^>]*>/i', function (array $match) use ($resolveImageUrl): string {
            $url = $resolveImageUrl($match[1]);

            if ($url === null) {
                return '';
            }

            $alt = preg_match('/alt="([^"]*)"/i', $match[0], $altMatch) ? $altMatch[1] : '';

            return "<img src=\"{$url}\" alt=\"{$alt}\" loading=\"lazy\">";
        }, $html);

        $html = (string) preg_replace_callback('/href="https?:\/\/(?:www\.)?'.preg_quote($legacyHost, '/').'(\/[^"]*)?"/i', function (array $match): string {
            $path = $match[1] ?? '/';

            return 'href="'.($path === '' ? '/' : $path).'"';
        }, $html);

        $html = (string) preg_replace('/<\/?(span|font)\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<\/?(div|section|article)\b[^>]*>/i', "\n\n", $html);
        $html = (string) preg_replace('/\s(class|style|dir|id|align|data-[\w-]+|aria-[\w-]+)="[^"]*"/i', '', $html);
        $html = $this->autoParagraph($html);
        $html = (string) preg_replace('/<(p|strong|em|b|li|h[1-6])>(\s|&nbsp;|<br\s*\/?>)*<\/\1>/iu', '', $html);
        $html = (string) preg_replace('/<ul>\s*<\/ul>|<ol>\s*<\/ol>/i', '', $html);
        $html = trim((string) preg_replace("/\n{2,}/", "\n", $html));

        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return [
            'html' => $html,
            'text' => $text,
            'gallery' => array_values(array_unique($gallery)),
            'downloads' => array_values(array_unique($downloads)),
        ];
    }

    /**
     * Simplified wpautop(): wraps loose text blocks in paragraphs.
     */
    private function autoParagraph(string $html): string
    {
        $html = (string) preg_replace('/(<(?:ul|ol|table|blockquote|h[1-6]|figure|pre|p)\b[^>]*>)/i', "\n\n$1", $html);
        $html = (string) preg_replace('/(<\/(?:ul|ol|table|blockquote|h[1-6]|figure|pre|p)>|<hr\s*\/?>)/i', "$1\n\n", $html);
        $blocks = preg_split('/\n\s*\n/', trim($html)) ?: [];
        $output = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (preg_match('/^<('.self::BLOCK_TAGS.')\b/i', $block)) {
                $output[] = $block;

                continue;
            }

            $output[] = '<p>'.preg_replace('/\n+/', '<br>', $block).'</p>';
        }

        return implode("\n", $output);
    }
}

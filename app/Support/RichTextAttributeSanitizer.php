<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Narrows the `class` and `style` attributes of rich text to what the panel's editor writes. Without it, a text
 * could borrow the site's own classes or inline CSS (position: fixed, z-index…) to cover the page with a fake
 * notice. Applies to every `sanitizeHtml()` call, on top of Filament's sanitizer.
 */
final class RichTextAttributeSanitizer implements AttributeSanitizerInterface
{
    /**
     * Classes the editor adds: lead paragraphs, text colours and column layouts.
     */
    private const ALLOWED_CLASSES = ['lead', 'color', 'grid-layout', 'grid-layout-col'];

    /**
     * Alignment, table and image sizes and text colours. Nothing that takes an element out of its place.
     */
    private const ALLOWED_PROPERTIES = [
        'text-align', 'vertical-align', 'color', 'background-color', 'font-weight', 'font-style', 'text-decoration',
        'width', 'min-width', 'max-width', 'height',
    ];

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['class', 'style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $sanitized = $attribute === 'class' ? $this->classes($value) : $this->declarations($value);

        return $sanitized === '' ? null : $sanitized;
    }

    private function classes(string $value): string
    {
        return collect(preg_split('/\s+/', trim($value)) ?: [])
            ->filter(fn (string $class): bool => in_array($class, self::ALLOWED_CLASSES, true) || preg_match('/^language-[a-z0-9+-]+$/', $class) === 1)
            ->implode(' ');
    }

    private function declarations(string $value): string
    {
        $declarations = [];

        foreach (explode(';', $value) as $declaration) {
            [$property, $propertyValue] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $propertyValue = trim($propertyValue);

            $isAllowed = in_array($property, self::ALLOWED_PROPERTIES, true) || preg_match('/^--[a-z0-9-]+$/', $property) === 1;

            // Function calls that load or evaluate something, escapes that could hide them, and markup.
            if (! $isAllowed || $propertyValue === '' || preg_match('/url\s*\(|expression|image-set|javascript:|!important|[\\\\<>@{}]/i', $propertyValue) === 1) {
                continue;
            }

            $declarations[] = $property.': '.$propertyValue;
        }

        return implode('; ', $declarations);
    }
}

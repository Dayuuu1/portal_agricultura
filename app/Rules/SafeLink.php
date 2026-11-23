<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A link the portal can print in an href or redirect to: a path of the portal itself ("/convocatorias") or a web
 * address. Rejects «javascript:», «data:» and protocol-relative addresses ("//otro-sitio"), which would let whoever
 * edits the value run code in the visitor's browser or send them elsewhere unnoticed.
 */
class SafeLink implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isSafe($value)) {
            $fail('Escriba una ruta del portal que empiece con «/» (por ejemplo, /convocatorias) o una dirección web que empiece con https://.');
        }
    }

    public static function isSafe(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || preg_match('/[\s\x00-\x1F\\\\]/', $value) === 1) {
            return false;
        }

        if (str_starts_with($value, '/')) {
            return ! str_starts_with($value, '//');
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
            && Str::isUrl($value, ['http', 'https']);
    }
}

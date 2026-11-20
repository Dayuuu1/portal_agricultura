<?php

namespace App\Support\WordPress;

/**
 * Flags the casino, forex and crypto posts injected into the former site, as well
 * as any post that is not written in Spanish.
 */
final class SpamDetector
{
    private const KEYWORDS = '/(?<!\p{L})(casinos?|kasyno|slots?|betting|mostbet|1xbet|1win|pin-?up|vulkan|forex|bitcoin|crypto|cryptocurrency|blockchain|brokers?|trading|traders?|scalping|coinmama|dukascopy|binary options?|payday|loans?|viagra|cialis|essay|dating|firmware|stock market|bookkeeping|kursy?|notowania|walut)(?!\p{L})/iu';

    private const SPANISH_WORDS = ['de', 'la', 'el', 'en', 'y', 'los', 'las', 'del', 'con', 'para', 'por', 'que', 'se', 'al', 'una', 'un', 'su', 'sus', 'es', 'como', 'más', 'lo', 'a'];

    private const FOREIGN_WORDS = ['the', 'and', 'of', 'to', 'for', 'is', 'with', 'on', 'best', 'your', 'you', 'how', 'are', 'this', 'that', 'from', 'what', 'w', 'i', 'na', 'z', 'się', 'jest', 'oraz', 'dla', 'nie', 'und', 'der', 'die', 'das', 'mit'];

    public function isSpam(string $title, string $content = ''): bool
    {
        if (preg_match(self::KEYWORDS, $title)) {
            return true;
        }

        $sample = $title.' '.mb_substr(strip_tags($content), 0, 4000);

        if (preg_match('/\p{Cyrillic}{3,}|\p{Thai}+/u', $sample)) {
            return true;
        }

        if (preg_match_all(self::KEYWORDS, $sample) >= 3) {
            return true;
        }

        return $content !== '' && $this->isForeignLanguage($title.' '.mb_substr(strip_tags((string) preg_replace('/\[[^\]]*\]/', ' ', $content)), 0, 800));
    }

    private function isForeignLanguage(string $text): bool
    {
        preg_match_all('/\p{L}+/u', mb_strtolower($text), $matches);
        $words = $matches[0];

        if (count($words) < 4) {
            return false;
        }

        $spanish = count(array_intersect($words, self::SPANISH_WORDS));
        $foreign = count(array_intersect($words, self::FOREIGN_WORDS));

        return ($foreign >= 2 && $foreign > $spanish) || ($spanish === 0 && count($words) >= 25);
    }
}

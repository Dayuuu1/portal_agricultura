<?php

namespace App\Support\Analytics;

use App\Enums\DeviceType;

/**
 * Coarse reading of the browser signature: kind of device, browser and system families, and whether it is a robot.
 * Only these families are kept; the signature itself is never stored.
 */
final class UserAgent
{
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|headlesschrome|phantomjs|lighthouse|pagespeed|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|monitor|uptime|scrapy|httpclient/i';

    public function __construct(private readonly string $signature) {}

    public static function from(?string $signature): self
    {
        return new self(mb_substr((string) $signature, 0, 500));
    }

    public function isBot(): bool
    {
        return $this->signature === '' || preg_match(self::BOTS, $this->signature) === 1;
    }

    public function device(?int $screenWidth = null): DeviceType
    {
        return match (true) {
            preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $this->signature) === 1 => DeviceType::Tablet,
            preg_match('/mobi|iphone|ipod|android.*mobile|windows phone|blackberry|opera mini/i', $this->signature) === 1 => DeviceType::Mobile,
            // iPadOS presents itself as a Mac: the screen width tells it apart.
            $screenWidth !== null && $screenWidth > 0 && $screenWidth < 768 => DeviceType::Mobile,
            default => DeviceType::Desktop,
        };
    }

    public function browser(): string
    {
        return match (true) {
            str_contains($this->signature, 'Edg/') || str_contains($this->signature, 'EdgA/') || str_contains($this->signature, 'EdgiOS/') => 'Edge',
            str_contains($this->signature, 'OPR/') || str_contains($this->signature, 'Opera') => 'Opera',
            str_contains($this->signature, 'SamsungBrowser/') => 'Samsung Internet',
            str_contains($this->signature, 'FBAN') || str_contains($this->signature, 'FBAV') || str_contains($this->signature, 'Instagram') => 'Facebook / Instagram',
            str_contains($this->signature, 'Firefox/') || str_contains($this->signature, 'FxiOS/') => 'Firefox',
            str_contains($this->signature, 'Chrome/') || str_contains($this->signature, 'CriOS/') => 'Chrome',
            str_contains($this->signature, 'Safari/') => 'Safari',
            default => 'Otro',
        };
    }

    public function os(): string
    {
        return match (true) {
            str_contains($this->signature, 'Android') => 'Android',
            preg_match('/iPhone|iPad|iPod/', $this->signature) === 1 => 'iOS',
            str_contains($this->signature, 'Windows') => 'Windows',
            str_contains($this->signature, 'Mac OS X') || str_contains($this->signature, 'Macintosh') => 'macOS',
            str_contains($this->signature, 'CrOS') => 'ChromeOS',
            str_contains($this->signature, 'Linux') => 'Linux',
            default => 'Otro',
        };
    }
}

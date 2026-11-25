<?php

namespace Database\Factories;

use App\Enums\DeviceType;
use App\Enums\SiteSection;
use App\Enums\TrafficSource;
use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    public function definition(): array
    {
        $at = now();

        return [
            'visitor' => Str::random(16),
            'session' => Str::random(16),
            'is_entry' => true,
            'path' => '/',
            'title' => 'Inicio',
            'section' => SiteSection::Home,
            'source' => TrafficSource::Direct,
            'device' => DeviceType::Mobile,
            'browser' => 'Chrome',
            'os' => 'Android',
            'duration' => fake()->numberBetween(5, 240),
            'date' => $at->format('Y-m-d'),
            'hour' => (int) $at->format('G'),
            'created_at' => $at,
        ];
    }

    /**
     * Another page of the same visit, after the first one.
     */
    public function inVisit(PageView $first, string $path = '/noticias', int $minutesLater = 2): static
    {
        return $this->state(fn (): array => [
            'visitor' => $first->visitor,
            'session' => $first->session,
            'is_entry' => false,
            'source' => null,
            'path' => $path,
            'section' => SiteSection::fromPath($path),
            'date' => $first->date,
            'hour' => $first->hour,
            'created_at' => $first->created_at->copy()->addMinutes($minutesLater),
        ]);
    }

    public function on(string $date, int $hour = 10): static
    {
        return $this->state(fn (): array => ['date' => $date, 'hour' => $hour, 'created_at' => $date.' '.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00:00']);
    }
}

<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementStat>
 */
class AnnouncementStatFactory extends Factory
{
    public function definition(): array
    {
        $views = fake()->numberBetween(20, 400);

        return [
            'announcement_id' => Announcement::factory(),
            'date' => today(),
            'views' => $views,
            'clicks' => (int) round($views * fake()->randomFloat(2, 0.01, 0.12)),
            'dismissals' => (int) round($views * fake()->randomFloat(2, 0.4, 0.8)),
        ];
    }
}

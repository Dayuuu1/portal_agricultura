<?php

namespace Database\Factories;

use App\Enums\AnnouncementFrequency;
use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLayout;
use App\Enums\AnnouncementPlacement;
use App\Models\Announcement;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kind' => AnnouncementKind::Notice,
            'title' => fake()->sentence(5),
            'body' => fake()->sentence(18),
            'layout' => AnnouncementLayout::Card,
            'button_text' => 'Ver más',
            'button_url' => '/convocatorias',
            'placement' => AnnouncementPlacement::Home,
            'frequency' => AnnouncementFrequency::Once,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function everywhere(): static
    {
        return $this->state(['placement' => AnnouncementPlacement::Everywhere]);
    }

    /**
     * Highlights a news post and leaves text and button empty, so they come from the post.
     */
    public function forPost(?Post $post = null): static
    {
        return $this->state(fn (): array => [
            'kind' => AnnouncementKind::Highlight,
            'post_id' => $post?->id ?? Post::factory(),
            'body' => null,
            'button_text' => null,
            'button_url' => null,
        ]);
    }
}

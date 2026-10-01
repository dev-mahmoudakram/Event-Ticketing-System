<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPage>
 */
class EventPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'key' => null,
            'slug' => $this->faker->unique()->slug(2),
            'title_ar' => 'صفحة',
            'title_en' => ucfirst($this->faker->words(2, true)),
            'body_ar' => '<p>محتوى</p>',
            'body_en' => '<p>Content</p>',
            'show_in_footer' => true,
            'is_published' => true,
            'sort_order' => 10,
        ];
    }
}

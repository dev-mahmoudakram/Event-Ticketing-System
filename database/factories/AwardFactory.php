<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Award;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    public function definition(): array
    {
        $category = $this->faker->randomElement(['Creator of the Year', 'Best Newcomer', 'Best Studio']);

        return [
            'event_id' => Event::factory(),
            'category_ar' => $category,
            'category_en' => $category,
            'nominee_name_ar' => $this->faker->name(),
            'nominee_name_en' => $this->faker->name(),
            'sort_order' => 0,
        ];
    }
}

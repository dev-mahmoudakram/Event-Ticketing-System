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
        $name = $this->faker->randomElement(['Creator of the Year', 'Best Newcomer', 'Best Studio']);

        return [
            'event_id' => Event::factory(),
            'name_ar' => $name,
            'name_en' => $name,
            'sort_order' => 0,
        ];
    }
}

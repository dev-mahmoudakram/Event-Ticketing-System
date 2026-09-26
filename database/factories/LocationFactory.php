<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => 'قاعة '.$this->faker->unique()->numberBetween(1, 999),
            'name_en' => 'Room '.$this->faker->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}

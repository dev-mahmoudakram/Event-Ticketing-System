<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\SessionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionType>
 */
class SessionTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => 'نوع '.$this->faker->unique()->numberBetween(1, 99999),
            'name_en' => ucfirst($this->faker->unique()->words(2, true)),
            'is_break' => false,
            'sort_order' => 10,
        ];
    }
}

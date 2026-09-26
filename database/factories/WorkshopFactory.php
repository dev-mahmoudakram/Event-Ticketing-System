<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkshopFactory extends Factory
{
    protected $model = Workshop::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'slug' => $this->faker->unique()->slug(3),
            'name_ar' => $this->faker->sentence(3),
            'name_en' => $this->faker->sentence(3),
            'day_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'start_time' => '11:00',
            'end_time' => '12:30',
            'location_id' => null,
            'description_ar' => $this->faker->paragraph(),
            'description_en' => $this->faker->paragraph(),
            'capacity' => $this->faker->numberBetween(10, 50),
            'sort_order' => 0,
        ];
    }

    public function unscheduled(): static
    {
        return $this->state(fn (array $attributes) => ['day_date' => null, 'start_time' => null, 'end_time' => null]);
    }
}

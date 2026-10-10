<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'description_ar' => $this->faker->sentence(),
            'description_en' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(200, 2000),
            'currency' => 'EGP',
            'workshop_slot_count' => $this->faker->randomElement([0, 1, 2]),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}

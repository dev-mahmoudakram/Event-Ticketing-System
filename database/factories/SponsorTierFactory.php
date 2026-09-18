<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\SponsorTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorTierFactory extends Factory
{
    protected $model = SponsorTier::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => $this->faker->randomElement(['بلاتينيوم', 'ذهبي', 'فضي', 'برونزي']),
            'name_en' => $this->faker->randomElement(['Platinum', 'Gold', 'Silver', 'Bronze']),
            'sort_order' => 0,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\InfluencerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class InfluencerCategoryFactory extends Factory
{
    protected $model = InfluencerCategory::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => $this->faker->randomElement(['طعام', 'موضة', 'تقنية', 'سفر']),
            'name_en' => $this->faker->randomElement(['Food', 'Fashion', 'Tech', 'Travel']),
            'sort_order' => 0,
        ];
    }
}

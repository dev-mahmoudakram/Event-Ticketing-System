<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => $this->faker->company(),
            'name_en' => $this->faker->company(),
            'logo_path' => null,
            'sponsor_tier_id' => null,
            'website_url' => $this->faker->url(),
            'sort_order' => 0,
        ];
    }
}

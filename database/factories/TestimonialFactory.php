<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'photo_path' => null,
            'quote_ar' => $this->faker->paragraph(),
            'quote_en' => $this->faker->paragraph(),
            'name_ar' => $this->faker->name(),
            'name_en' => $this->faker->name(),
            'title_ar' => $this->faker->jobTitle(),
            'title_en' => $this->faker->jobTitle(),
            'sort_order' => 0,
        ];
    }
}

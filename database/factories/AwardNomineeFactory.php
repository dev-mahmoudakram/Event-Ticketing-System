<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Award;
use App\Models\AwardNominee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AwardNominee>
 */
class AwardNomineeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'award_id' => Award::factory(),
            'name_ar' => $this->faker->name(),
            'name_en' => $this->faker->name(),
            'bio_ar' => $this->faker->sentence(),
            'bio_en' => $this->faker->sentence(),
            'image_path' => null,
            'sort_order' => 0,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AudienceTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudienceTab>
 */
class AudienceTabFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = $this->faker->words(3, true);

        return [
            'label_en' => $label,
            'label_ar' => $label.' (ar)',
            'lede_en' => $this->faker->sentence(),
            'lede_ar' => $this->faker->sentence(),
            'cta_en' => 'Get in touch',
            'cta_ar' => 'تواصل معنا',
            'sort_order' => 0,
        ];
    }
}

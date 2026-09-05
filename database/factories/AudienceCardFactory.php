<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AudienceCard;
use App\Models\AudienceTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudienceCard>
 */
class AudienceCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'audience_tab_id' => AudienceTab::factory(),
            'title_en' => $title,
            'title_ar' => $title.' (ar)',
            'body_en' => $this->faker->paragraph(),
            'body_ar' => $this->faker->paragraph(),
            'sort_order' => 0,
        ];
    }
}

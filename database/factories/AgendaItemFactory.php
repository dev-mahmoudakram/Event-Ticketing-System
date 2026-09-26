<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\SessionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgendaItemFactory extends Factory
{
    protected $model = AgendaItem::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'session_type_id' => fn (array $attributes) => SessionType::where('event_id', $attributes['event_id'])->where('name_en', 'Session')->value('id')
                ?? SessionType::factory()->create(['event_id' => $attributes['event_id']])->id,
            'location_id' => null,
            'day_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title_ar' => $this->faker->sentence(3),
            'title_en' => $this->faker->sentence(3),
            'description_ar' => null,
            'description_en' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * One of the event's own types, by its English name ("Keynote", "Break", …).
     */
    public function ofType(string $nameEn): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type_id' => fn (array $resolved) => SessionType::where('event_id', $resolved['event_id'])->where('name_en', $nameEn)->value('id'),
        ]);
    }
}

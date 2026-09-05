<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCoupon;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCoupon>
 */
class DiscountCouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('SAVE##??')),
            'type' => DiscountType::Percentage,
            'value' => 20,
            'usage_limit' => null,
            'times_used' => 0,
            'is_active' => true,
        ];
    }

    public function fixed(int $amount): self
    {
        return $this->state(fn () => ['type' => DiscountType::Fixed, 'value' => $amount]);
    }

    public function expired(): self
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function spent(): self
    {
        return $this->state(fn () => ['usage_limit' => 5, 'times_used' => 5]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'token' => Str::random(40),
            'otp' => (string) $this->faker->numberBetween(100000, 999999),
            'status' => InvitationStatus::Unused,
            'expires_at' => now()->addDays(7),
        ];
    }
}

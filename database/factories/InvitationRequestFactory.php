<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationRequest>
 */
class InvitationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'event_id' => Event::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '+201001234567',
        ];
    }
}

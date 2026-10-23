<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AwardVote>
 */
class AwardVoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => null,
            'award_id' => Award::factory(),
            'award_nominee_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'ip_address' => $this->faker->ipv4(),
            'removed_at' => null,
            'removed_by' => null,
        ];
    }

    /**
     * Fill the event and nominee from the award when the caller did not pick them.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AwardVote $vote): void {
            $vote->event_id ??= Award::findOrFail($vote->award_id)->event_id;
            $vote->award_nominee_id ??= AwardNominee::factory()->create(['award_id' => $vote->award_id])->id;
        });
    }

    public function removed(): static
    {
        return $this->state(fn () => ['removed_at' => now()]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketPaymentStatus;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketPayment>
 */
class TicketPaymentFactory extends Factory
{
    protected $model = TicketPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessionId = $this->faker->regexify('[a-f0-9]{24}');

        return [
            'ticket_id' => Ticket::factory(),
            'merchant_order_id' => 'TEST-'.$this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'session_id' => $sessionId,
            'session_url' => 'https://payments.kashier.io/session/'.$sessionId.'?mode=test',
            'amount' => 500,
            'currency' => 'EGP',
            'status' => TicketPaymentStatus::Pending,
            'expires_at' => now()->addDay(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => TicketPaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}

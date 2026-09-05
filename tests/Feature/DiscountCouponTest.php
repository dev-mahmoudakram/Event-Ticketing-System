<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\DiscountCoupon;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DiscountCouponTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private TicketType $ticketType;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->event = Event::factory()->create(['status' => 'published', 'slug' => 'ccs-2026']);
        $this->ticketType = TicketType::factory()->for($this->event)->create(['price' => 1000, 'is_active' => true]);
    }

    private function request(array $overrides = []): TestResponse
    {
        return $this->post(route('ticket-requests.store', $this->event), array_merge([
            'ticket_type_id' => $this->ticketType->id,
            'name' => 'Kareem Al-Sayed',
            'email' => 'kareem@example.com',
            'phone' => '+201001234567',
        ], $overrides));
    }

    public function test_a_percentage_coupon_comes_off_the_price(): void
    {
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20', 'value' => 20]);

        $this->request(['coupon_code' => 'SAVE20']);

        $ticket = Ticket::firstOrFail();
        $this->assertSame(1000, $ticket->price);
        $this->assertSame(200, $ticket->discount_amount);
    }

    public function test_a_fixed_coupon_comes_off_the_price(): void
    {
        DiscountCoupon::factory()->for($this->event)->fixed(250)->create(['code' => 'FLAT250']);

        $this->request(['coupon_code' => 'FLAT250']);

        $this->assertSame(250, Ticket::firstOrFail()->discount_amount);
    }

    public function test_a_fixed_coupon_never_exceeds_the_price(): void
    {
        DiscountCoupon::factory()->for($this->event)->fixed(5000)->create(['code' => 'HUGE']);

        $this->request(['coupon_code' => 'HUGE']);

        $this->assertSame(1000, Ticket::firstOrFail()->discount_amount);
    }

    public function test_a_code_is_matched_whatever_the_casing_or_spacing(): void
    {
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20', 'value' => 20]);

        $this->request(['coupon_code' => '  save20 ']);

        $this->assertSame(200, Ticket::firstOrFail()->discount_amount);
    }

    public function test_an_unknown_code_lets_the_request_through_at_full_price(): void
    {
        $this->request(['coupon_code' => 'NOPE'])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertSame(0, $ticket->discount_amount);
        $this->assertNull($ticket->discount_coupon_id);
        $this->assertSame(TicketStatus::Pending, $ticket->status);
    }

    public function test_an_expired_or_spent_or_inactive_coupon_is_refused(): void
    {
        DiscountCoupon::factory()->for($this->event)->expired()->create(['code' => 'OLD']);
        DiscountCoupon::factory()->for($this->event)->spent()->create(['code' => 'GONE']);
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'OFF', 'is_active' => false]);

        foreach (['OLD', 'GONE', 'OFF'] as $code) {
            Ticket::query()->delete();
            $this->request(['coupon_code' => $code]);
            $this->assertSame(0, Ticket::firstOrFail()->discount_amount, $code.' should not apply.');
        }
    }

    public function test_a_coupon_belonging_to_another_event_is_refused(): void
    {
        DiscountCoupon::factory()->create(['code' => 'OTHER', 'value' => 50]);

        $this->request(['coupon_code' => 'OTHER']);

        $this->assertSame(0, Ticket::firstOrFail()->discount_amount);
    }

    public function test_redemption_is_counted(): void
    {
        $coupon = DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20', 'usage_limit' => 2]);

        $this->request(['coupon_code' => 'SAVE20']);

        $this->assertSame(1, $coupon->fresh()->times_used);
        $this->assertSame(1, $coupon->fresh()->remainingUses());
    }

    public function test_the_price_is_remembered_even_if_the_ticket_type_changes_later(): void
    {
        $this->request();
        $this->ticketType->update(['price' => 4000]);

        $this->assertSame(1000, Ticket::firstOrFail()->price);
    }

    public function test_the_code_field_only_appears_when_the_event_runs_codes(): void
    {
        $this->get(route('landing.show', $this->event).'?lang=en')->assertDontSee('name="coupon_code"', false);

        DiscountCoupon::factory()->for($this->event)->create();

        $this->get(route('landing.show', $this->event).'?lang=en')->assertSee('name="coupon_code"', false);
    }

    public function test_an_admin_can_manage_coupons(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.discount-coupons.store', $this->event), [
            'code' => 'early-bird',
            'type' => 'percentage',
            'value' => 15,
        ])->assertRedirect(route('admin.events.discount-coupons.index', $this->event));

        // Stored uppercased, so what the admin types and what the attendee types both match.
        $this->assertDatabaseHas('discount_coupons', ['code' => 'EARLY-BIRD', 'value' => 15]);
    }

    public function test_a_percentage_over_one_hundred_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.discount-coupons.store', $this->event), [
                'code' => 'TOOMUCH', 'type' => 'percentage', 'value' => 150,
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_two_events_may_use_the_same_code(): void
    {
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'SHARED']);
        $other = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.discount-coupons.store', $other), [
                'code' => 'SHARED', 'type' => 'percentage', 'value' => 10,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_the_same_code_cannot_be_created_twice_for_one_event(): void
    {
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.discount-coupons.store', $this->event), [
                'code' => 'SAVE20', 'type' => 'percentage', 'value' => 10,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_deleting_a_coupon_leaves_the_tickets_it_discounted_intact(): void
    {
        $coupon = DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20']);
        $this->request(['coupon_code' => 'SAVE20']);

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.events.discount-coupons.destroy', [$this->event, $coupon]));

        $ticket = Ticket::firstOrFail();
        $this->assertSame(200, $ticket->discount_amount, 'What someone paid must survive the coupon.');
        $this->assertNull($ticket->discount_coupon_id);
    }

    public function test_guests_cannot_manage_coupons(): void
    {
        $this->get(route('admin.events.discount-coupons.index', $this->event))->assertRedirect(route('admin.login'));
    }
}

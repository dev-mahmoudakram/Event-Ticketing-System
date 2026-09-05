<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\PlatformReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlatformReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * created_at is not fillable on Ticket, so a test that needs a ticket dated in the past
     * has to stamp it after creation rather than passing it in and quietly getting today.
     */
    private function ticket(Event $event, array $attributes = []): Ticket
    {
        static $n = 0;
        $n++;

        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $ticket = Ticket::create(array_merge([
            'event_id' => $event->id,
            'ticket_type_id' => TicketType::factory()->for($event)->create(['currency' => 'EGP'])->id,
            'name' => 'Attendee '.$n,
            'email' => 'attendee'.$n.'@example.com',
            'phone' => '+2010012345'.$n,
            'ticket_number' => 'REF-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
            'price' => 1000,
            'discount_amount' => 0,
        ], $attributes));

        if ($createdAt !== null) {
            $ticket->forceFill(['created_at' => $createdAt])->save();
        }

        return $ticket;
    }

    public function test_totals_count_across_every_event(): void
    {
        $first = Event::factory()->create();
        $second = Event::factory()->create();

        $this->ticket($first, ['checked_in_at' => now()]);
        $this->ticket($first, ['price' => 500]);
        $this->ticket($second, ['is_paid' => false, 'status' => TicketStatus::Pending]);

        $totals = (new PlatformReport)->totals();

        $this->assertSame(2, $totals['events']);
        $this->assertSame(3, $totals['tickets']);
        $this->assertSame(1, $totals['attendees']);
        $this->assertSame(1500, $totals['revenue']);
    }

    public function test_revenue_is_counted_after_discounts(): void
    {
        $event = Event::factory()->create();
        $this->ticket($event, ['price' => 1000, 'discount_amount' => 250]);

        $this->assertSame(750, (new PlatformReport)->totals()['revenue']);
    }

    public function test_each_event_is_listed_with_its_own_numbers(): void
    {
        $first = Event::factory()->create(['name_en' => 'Summit One']);
        $second = Event::factory()->create(['name_en' => 'Summit Two']);

        $this->ticket($first, ['checked_in_at' => now()]);
        $this->ticket($first);
        $this->ticket($second, ['is_paid' => false, 'status' => TicketStatus::Pending]);

        $rows = (new PlatformReport)->byEvent()->keyBy('name');

        $this->assertSame(2, $rows['Summit One']['requested']);
        $this->assertSame(2, $rows['Summit One']['paid']);
        $this->assertSame(1, $rows['Summit One']['arrived']);
        $this->assertSame(1, $rows['Summit Two']['requested']);
        $this->assertSame(0, $rows['Summit Two']['paid']);
    }

    public function test_growth_covers_twelve_months_including_the_quiet_ones(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');

        $event = Event::factory()->create();
        $this->ticket($event, ['created_at' => Carbon::parse('2026-09-02')]);
        $this->ticket($event, ['created_at' => Carbon::parse('2026-07-04')]);

        $growth = (new PlatformReport)->growth();

        $this->assertCount(12, $growth);
        $this->assertSame('2025-10', $growth->first()['month']);
        $this->assertSame('2026-09', $growth->last()['month']);
        $this->assertSame(1, $growth->firstWhere('month', '2026-09')['requested']);
        $this->assertSame(1, $growth->firstWhere('month', '2026-07')['requested']);
        // A month with nothing in it is a zero, not a missing row.
        $this->assertSame(0, $growth->firstWhere('month', '2026-08')['requested']);

        Carbon::setTestNow();
    }

    public function test_growth_reports_revenue_only_for_paid_tickets(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');

        $event = Event::factory()->create();
        $this->ticket($event, ['created_at' => Carbon::parse('2026-09-02'), 'price' => 800]);
        $this->ticket($event, [
            'created_at' => Carbon::parse('2026-09-03'),
            'price' => 900,
            'is_paid' => false,
            'status' => TicketStatus::Pending,
        ]);

        $september = (new PlatformReport)->growth()->firstWhere('month', '2026-09');

        $this->assertSame(2, $september['requested']);
        $this->assertSame(1, $september['paid']);
        $this->assertSame(800, $september['revenue']);

        Carbon::setTestNow();
    }

    public function test_quality_rates_measure_each_step_against_the_one_before(): void
    {
        $event = Event::factory()->create();

        // Four reviewed, three approved, two paid, one arrived.
        $this->ticket($event, ['status' => TicketStatus::Rejected, 'is_paid' => false]);
        $this->ticket($event, ['status' => TicketStatus::PaymentPending, 'is_paid' => false]);
        $this->ticket($event, ['status' => TicketStatus::TicketIssued]);
        $this->ticket($event, ['status' => TicketStatus::CheckedIn, 'checked_in_at' => now()]);

        $quality = (new PlatformReport)->quality();

        $this->assertSame(4, $quality['reviewed']);
        $this->assertSame(3, $quality['approved']);
        $this->assertSame(2, $quality['paid']);
        $this->assertSame(1, $quality['arrived']);
        $this->assertSame(75, $quality['approval_rate']);
        $this->assertSame(67, $quality['payment_rate']);
        $this->assertSame(50, $quality['show_up_rate']);
    }

    public function test_rates_are_zero_rather_than_a_division_error_when_nothing_happened(): void
    {
        $quality = (new PlatformReport)->quality();

        $this->assertSame(0, $quality['approval_rate']);
        $this->assertSame(0, $quality['payment_rate']);
        $this->assertSame(0, $quality['show_up_rate']);
    }

    public function test_the_report_page_is_closed_to_visitors_who_are_not_signed_in(): void
    {
        $this->get(route('admin.reports.show'))->assertRedirect(route('admin.login'));
    }

    public function test_the_report_page_renders_with_its_charts(): void
    {
        $event = Event::factory()->create(['name_en' => 'Summit One']);
        $this->ticket($event, ['checked_in_at' => now()]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.show').'?lang=en');

        $response->assertOk();
        $response->assertSee('Platform report');
        $response->assertSee('Summit One');
        $response->assertSee('data-chart', false);
    }

    public function test_the_report_page_stands_up_before_any_event_exists(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.show').'?lang=en')
            ->assertOk()
            ->assertDontSee('data-chart', false);
    }
}

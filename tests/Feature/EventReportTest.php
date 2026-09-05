<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\DiscountCoupon;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\EventReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventReportTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private TicketType $ticketType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = Event::factory()->create(['status' => 'published']);
        $this->ticketType = TicketType::factory()->for($this->event)->create(['price' => 1000, 'currency' => 'EGP', 'name_en' => 'General']);
    }

    private function ticket(array $attributes = []): Ticket
    {
        static $n = 0;
        $n++;

        return Ticket::create(array_merge([
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->ticketType->id,
            'name' => 'Attendee '.$n,
            'email' => 'attendee'.$n.'@example.com',
            'phone' => '+20100000000'.$n,
            'ticket_number' => 'CCS-'.$n,
            'status' => TicketStatus::Pending,
            'price' => 1000,
            'discount_amount' => 0,
        ], $attributes));
    }

    public function test_it_counts_every_status_including_the_empty_ones(): void
    {
        $this->ticket();
        $this->ticket(['status' => TicketStatus::Rejected]);

        $statuses = (new EventReport($this->event))->ticketsByStatus();

        $this->assertCount(count(TicketStatus::cases()), $statuses);
        $this->assertSame(1, $statuses->firstWhere('status', TicketStatus::Pending)['count']);
        $this->assertSame(0, $statuses->firstWhere('status', TicketStatus::Cancelled)['count']);
    }

    public function test_the_funnel_counts_each_step(): void
    {
        $this->ticket();
        $this->ticket(['status' => TicketStatus::Rejected]);
        $this->ticket(['status' => TicketStatus::PaymentPending]);
        $this->ticket(['status' => TicketStatus::TicketIssued, 'is_paid' => true]);
        $this->ticket(['status' => TicketStatus::CheckedIn, 'is_paid' => true, 'checked_in_at' => now()]);

        $funnel = (new EventReport($this->event))->funnel();

        $this->assertSame(5, $funnel['requested']);
        $this->assertSame(3, $funnel['approved'], 'Pending and rejected are not approved.');
        $this->assertSame(2, $funnel['paid']);
        $this->assertSame(1, $funnel['checked_in']);
    }

    public function test_revenue_counts_what_was_actually_paid(): void
    {
        $this->ticket(['is_paid' => true]);
        $this->ticket(['is_paid' => true, 'discount_amount' => 200]);
        $this->ticket(['status' => TicketStatus::PaymentPending]);

        $revenue = (new EventReport($this->event))->revenue();

        $this->assertSame(1800, $revenue['collected'], '1000 + (1000 - 200)');
        $this->assertSame(200, $revenue['discounted']);
        $this->assertSame(1000, $revenue['outstanding'], 'Approved but unpaid is owed, not earned.');
        $this->assertSame('EGP', $revenue['currency']);
    }

    public function test_revenue_is_broken_down_by_ticket_type(): void
    {
        $vip = TicketType::factory()->for($this->event)->create(['price' => 5000, 'name_en' => 'VIP']);
        $this->ticket(['is_paid' => true]);
        $this->ticket(['ticket_type_id' => $vip->id, 'price' => 5000, 'is_paid' => true]);

        $rows = (new EventReport($this->event))->revenueByTicketType();

        $this->assertSame(1000, $rows->firstWhere('name', 'General')['revenue']);
        $this->assertSame(5000, $rows->firstWhere('name', 'VIP')['revenue']);
    }

    public function test_check_ins_are_grouped_by_hour(): void
    {
        $this->ticket(['is_paid' => true, 'checked_in_at' => '2026-08-15 09:10:00']);
        $this->ticket(['is_paid' => true, 'checked_in_at' => '2026-08-15 09:50:00']);
        $this->ticket(['is_paid' => true, 'checked_in_at' => '2026-08-15 11:05:00']);
        $this->ticket(['is_paid' => true]);

        $checkIns = (new EventReport($this->event))->checkIns();

        $this->assertSame(4, $checkIns['issued']);
        $this->assertSame(3, $checkIns['arrived']);
        $this->assertSame(2, $checkIns['by_hour']->firstWhere('hour', '2026-08-15 09:00')['count']);
    }

    public function test_coupon_use_is_reported_and_unused_codes_are_left_out(): void
    {
        $used = DiscountCoupon::factory()->for($this->event)->create(['code' => 'SAVE20']);
        DiscountCoupon::factory()->for($this->event)->create(['code' => 'UNUSED']);
        $this->ticket(['discount_coupon_id' => $used->id, 'discount_amount' => 200, 'is_paid' => true]);

        $coupons = (new EventReport($this->event))->coupons();

        $this->assertCount(1, $coupons);
        $this->assertSame('SAVE20', $coupons->first()['code']);
        $this->assertSame(200, $coupons->first()['discounted']);
    }

    public function test_one_event_never_reports_another_events_tickets(): void
    {
        $this->ticket(['is_paid' => true]);
        $other = Event::factory()->create();

        $this->assertSame(0, (new EventReport($other))->funnel()['requested']);
    }

    public function test_the_report_screen_renders(): void
    {
        $this->ticket(['is_paid' => true, 'checked_in_at' => now()]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.reports.show', $this->event).'?lang=en');

        $response->assertOk();
        $response->assertSee('Tickets by status');
        $response->assertSee('Revenue');
        $response->assertSee('On the day');
    }

    public function test_the_attendee_list_downloads_as_a_spreadsheet(): void
    {
        $this->ticket(['name' => 'كريم السيد', 'is_paid' => true]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.reports.export', $this->event));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'A BOM keeps Arabic readable in Excel.');
        $this->assertStringContainsString('reference,name,email', $csv);
        $this->assertStringContainsString('كريم السيد', $csv);
    }

    public function test_a_name_that_looks_like_a_formula_is_not_executed_by_a_spreadsheet(): void
    {
        // Names come from a public form; Excel runs a cell starting with =, +, - or @.
        $this->ticket(['name' => '=HYPERLINK("http://evil.test","click")', 'is_paid' => true]);
        $this->ticket(['name' => '+1 555 0000']);
        $this->ticket(['name' => '@someone']);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.reports.export', $this->event))
            ->streamedContent();

        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+1 555 0000", $csv);
        $this->assertStringContainsString("'@someone", $csv);
    }

    public function test_an_ordinary_name_is_left_alone(): void
    {
        $this->ticket(['name' => 'Kareem Al-Sayed', 'is_paid' => true]);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.reports.export', $this->event))
            ->streamedContent();

        $this->assertStringContainsString('Kareem Al-Sayed', $csv);
        $this->assertStringNotContainsString("'Kareem", $csv);
    }

    public function test_guests_cannot_read_the_report(): void
    {
        $this->get(route('admin.events.reports.show', $this->event))->assertRedirect(route('admin.login'));
        $this->get(route('admin.events.reports.export', $this->event))->assertRedirect(route('admin.login'));
    }
}

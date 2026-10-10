<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\TicketPaymentStatus;
use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Mail\TicketRequestApproved;
use App\Mail\TicketRequestRejected;
use App\Models\Event;
use App\Models\PaymentSetting;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Models\TicketRequestAnswer;
use App\Models\TicketRequestField;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_pending_tickets_by_default(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Ticket::factory()->for($event)->create(['status' => TicketStatus::Pending, 'name' => 'Pending Person']);
        Ticket::factory()->for($event)->create(['status' => TicketStatus::Rejected, 'name' => 'Rejected Person']);

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-requests.index', $event));

        $response->assertOk();
        $response->assertSee('Pending Person');
        $response->assertDontSee('Rejected Person');
    }

    public function test_admin_can_filter_by_status(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Ticket::factory()->for($event)->create(['status' => TicketStatus::Rejected, 'name' => 'Rejected Person']);

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-requests.index', $event).'?status=rejected');

        $response->assertOk();
        $response->assertSee('Rejected Person');
    }

    private function configureKashier(): void
    {
        PaymentSetting::create([
            'mode' => 'test',
            'merchant_id' => 'MID-1234-567',
            'api_key' => 'test-payment-api-key',
            'secret_key' => 'test-secret-key',
        ]);
    }

    /**
     * Kashier opening a session. Each call returns a new session id.
     */
    private function fakeKashier(string $checkoutHost = 'https://payments.kashier.io'): void
    {
        $count = 0;

        Http::fake([
            'test-api.kashier.io/v3/payment/sessions' => function () use (&$count, $checkoutHost) {
                $count++;

                return Http::response([
                    '_id' => 'session'.$count,
                    'status' => 'CREATED',
                    'sessionUrl' => $checkoutHost.'/session/session'.$count.'?mode=test',
                ]);
            },
            'test-api.kashier.io/v3/payment/sessions/*' => Http::response(['message' => 'success', 'data' => ['status' => 'PENDING']]),
        ]);
    }

    private function pendingRequest(Event $event, int $price = 1500, int $discount = 0): Ticket
    {
        $ticketType = TicketType::factory()->for($event)->create(['price' => $price, 'currency' => 'EGP']);

        return Ticket::factory()->for($event)->for($ticketType)->create([
            'status' => TicketStatus::Pending,
            'email' => 'attendee@example.com',
            'price' => $price,
            'discount_amount' => $discount,
        ]);
    }

    public function test_approve_moves_ticket_to_payment_pending_and_sends_mail(): void
    {
        Mail::fake();
        $this->configureKashier();
        $this->fakeKashier();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $response = $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']));

        $response->assertRedirect(route('admin.events.ticket-requests.index', $event));
        $this->assertSame(TicketStatus::PaymentPending, $ticket->fresh()->status);
        Mail::assertSent(TicketRequestApproved::class, fn ($mail) => $mail->hasTo('attendee@example.com')
            && $mail->paymentUrl === 'https://payments.kashier.io/session/session1?mode=test');
    }

    public function test_approving_opens_a_kashier_session_for_the_amount_due(): void
    {
        Mail::fake();
        $this->configureKashier();
        $this->fakeKashier();
        $this->freezeSecond();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event, price: 1500, discount: 300);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']));

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://test-api.kashier.io/v3/payment/sessions'
                && $request->header('Authorization') === ['test-secret-key']
                && $request->header('api-key') === ['test-payment-api-key']
                && $request['amount'] === '1200.00'
                && $request['currency'] === 'EGP'
                && $request['merchantId'] === 'MID-1234-567'
                && $request['maxFailureAttempts'] === 5
                && $request['type'] === 'one-time'
                && $request['expireAt'] === now()->addDay()->toIso8601ZuluString('millisecond')
                && $request['merchantRedirect'] === rtrim(config('app.url'), '/').'/payments/kashier/return'
                && $request['serverWebhook'] === rtrim(config('app.url'), '/').'/payments/kashier/webhook';
        });

        $payment = $ticket->payments()->sole();
        $this->assertSame(1200, $payment->amount);
        $this->assertSame('session1', $payment->session_id);
        $this->assertSame(TicketPaymentStatus::Pending, $payment->status);
        $this->assertSame($admin->id, $payment->sent_by);
        $this->assertTrue($payment->expires_at->equalTo(now()->addDay()));
    }

    public function test_kashier_urls_ignore_a_forged_host_header(): void
    {
        Mail::fake();
        $this->configureKashier();
        $this->fakeKashier();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)
            ->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']));

        Http::assertSent(fn (HttpRequest $request) => $request->method() === 'POST'
            && ! str_contains((string) $request['merchantRedirect'], 'attacker.example')
            && ! str_contains((string) $request['serverWebhook'], 'attacker.example'));
    }

    public function test_a_checkout_url_off_kashier_is_never_emailed(): void
    {
        Mail::fake();
        $this->configureKashier();
        $this->fakeKashier('https://evil.example');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
        $this->assertSame(0, $ticket->payments()->count());
        Mail::assertNothingSent();
    }

    public function test_approval_rolls_back_when_kashier_fails(): void
    {
        Mail::fake();
        $this->configureKashier();
        Http::fake(['test-api.kashier.io/*' => Http::response(['error' => 'bad'], 400)]);
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('error', __('The ticket was not approved because the payment link could not be created. Check the payment settings and try again.'));

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_approval_is_refused_until_payment_settings_are_complete(): void
    {
        Mail::fake();
        Http::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_approval_rolls_back_the_payment_link_when_the_email_fails(): void
    {
        $this->configureKashier();
        $this->fakeKashier();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail server down'));
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
        $this->assertSame(0, $ticket->payments()->count());
    }

    public function test_a_fully_discounted_ticket_is_issued_on_approval_without_kashier(): void
    {
        Mail::fake();
        Http::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event, price: 1500, discount: 1500);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $this->assertSame('free', $ticket->payment_method);
        Http::assertNothingSent();
        Mail::assertSent(TicketIssued::class, fn ($mail) => $mail->hasTo('attendee@example.com'));
        Mail::assertNotSent(TicketRequestApproved::class);
    }

    public function test_resending_closes_the_old_link_and_emails_a_new_one(): void
    {
        Mail::fake();
        $this->configureKashier();
        $this->fakeKashier();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);
        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']));

        $this->actingAs($admin)->post(route('admin.events.ticket-requests.resend-payment-link', [$event, $ticket]))
            ->assertSessionHas('success');

        $payments = $ticket->payments()->orderBy('id')->get();
        $this->assertCount(2, $payments);
        $this->assertSame(TicketPaymentStatus::Abandoned, $payments[0]->status);
        $this->assertSame(TicketPaymentStatus::Pending, $payments[1]->status);
        $this->assertNotSame($payments[0]->merchant_order_id, $payments[1]->merchant_order_id);
        Http::assertSent(fn (HttpRequest $request) => $request->method() === 'PATCH'
            && $request->url() === 'https://test-api.kashier.io/v3/payment/sessions/session1');
        Mail::assertSent(TicketRequestApproved::class, 2);
        Mail::assertSent(TicketRequestApproved::class, fn ($mail) => $mail->paymentUrl === 'https://payments.kashier.io/session/session2?mode=test');
    }

    public function test_resending_to_someone_who_already_paid_issues_their_ticket_instead(): void
    {
        Mail::fake();
        $this->configureKashier();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);
        $ticket->update(['status' => TicketStatus::PaymentPending]);
        $payment = TicketPayment::factory()->for($ticket)->create(['session_id' => 'session9', 'amount' => 1500, 'currency' => 'EGP']);
        Http::fake([
            'test-api.kashier.io/v3/payment/sessions/session9/payment' => Http::response(['message' => 'success', 'data' => [
                'status' => 'PAID', 'merchantOrderId' => $payment->merchant_order_id, 'amount' => '1500.00', 'currency' => 'EGP',
            ]]),
        ]);

        $this->actingAs($admin)->post(route('admin.events.ticket-requests.resend-payment-link', [$event, $ticket]))
            ->assertSessionHas('error', __('This ticket is no longer awaiting payment.'));

        $this->assertSame(TicketStatus::TicketIssued, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->payments()->count());
        Mail::assertNotSent(TicketRequestApproved::class);
        Mail::assertSent(TicketIssued::class);
    }

    public function test_only_a_ticket_awaiting_payment_can_get_a_new_link(): void
    {
        Mail::fake();
        $this->configureKashier();
        Http::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);

        $this->actingAs($admin)->post(route('admin.events.ticket-requests.resend-payment-link', [$event, $ticket]))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_a_ticket_from_another_event_cannot_get_a_new_link(): void
    {
        Mail::fake();
        Http::fake();
        $admin = User::factory()->create();
        $ticket = $this->pendingRequest(Event::factory()->create());

        $this->actingAs($admin)->post(route('admin.events.ticket-requests.resend-payment-link', [Event::factory()->create(), $ticket]))
            ->assertNotFound();
    }

    public function test_the_queue_offers_a_resend_for_tickets_awaiting_payment(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = $this->pendingRequest($event);
        $ticket->update(['status' => TicketStatus::PaymentPending]);
        TicketPayment::factory()->for($ticket)->create();

        $this->actingAs($admin)->get(route('admin.events.ticket-requests.index', [$event, 'status' => 'payment_pending']))
            ->assertOk()
            ->assertSee(route('admin.events.ticket-requests.resend-payment-link', [$event, $ticket]), false);
    }

    public function test_reject_moves_ticket_to_rejected_and_sends_mail(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create(['status' => TicketStatus::Pending, 'email' => 'attendee@example.com']);

        $response = $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'rejected']));

        $response->assertRedirect(route('admin.events.ticket-requests.index', $event));
        $this->assertSame(TicketStatus::Rejected, $ticket->fresh()->status);
        Mail::assertSent(TicketRequestRejected::class, fn ($mail) => $mail->hasTo('attendee@example.com'));
    }

    public function test_guest_cannot_download_an_answer_file(): void
    {
        Storage::fake('local');
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create();
        $field = TicketRequestField::factory()->for($event)->create(['type' => 'cv']);
        $path = 'ticket-uploads/'.$ticket->id.'/resume.pdf';
        Storage::disk('local')->put($path, 'fake-pdf-content');
        $answer = TicketRequestAnswer::factory()->create(['ticket_id' => $ticket->id, 'ticket_request_field_id' => $field->id, 'file_path' => $path, 'value' => null]);

        $response = $this->get(route('admin.events.ticket-requests.answers.download', [$event, $ticket, $answer]));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_download_an_answer_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create();
        $field = TicketRequestField::factory()->for($event)->create(['type' => 'cv']);
        $path = 'ticket-uploads/'.$ticket->id.'/resume.pdf';
        Storage::disk('local')->put($path, 'fake-pdf-content');
        $answer = TicketRequestAnswer::factory()->create(['ticket_id' => $ticket->id, 'ticket_request_field_id' => $field->id, 'file_path' => $path, 'value' => null]);

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-requests.answers.download', [$event, $ticket, $answer]));

        $response->assertOk();
    }

    public function test_downloading_an_answer_from_a_different_event_404s(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $ticket = Ticket::factory()->for($otherEvent)->create();
        $field = TicketRequestField::factory()->for($otherEvent)->create(['type' => 'cv']);
        $path = 'ticket-uploads/'.$ticket->id.'/resume.pdf';
        Storage::disk('local')->put($path, 'fake-pdf-content');
        $answer = TicketRequestAnswer::factory()->create(['ticket_id' => $ticket->id, 'ticket_request_field_id' => $field->id, 'file_path' => $path, 'value' => null]);

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-requests.answers.download', [$event, $ticket, $answer]));

        $response->assertStatus(404);
    }

    public function test_an_issued_ticket_cannot_be_approved_again(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create([
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
            'ticket_id' => str_repeat('a', 40),
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']));

        $response->assertRedirect(route('admin.events.ticket-requests.index', $event));
        $response->assertSessionHas('error');
        $this->assertSame(TicketStatus::TicketIssued, $ticket->fresh()->status);
        $this->assertSame(str_repeat('a', 40), $ticket->fresh()->ticket_id);
        Mail::assertNothingSent();
    }

    public function test_a_rejected_ticket_cannot_be_approved(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create(['status' => TicketStatus::Rejected]);

        $this->actingAs($admin)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::Rejected, $ticket->fresh()->status);
        Mail::assertNothingSent();
    }
}

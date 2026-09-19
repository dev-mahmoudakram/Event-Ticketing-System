# Event Invitations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin invite a specific person to request a ticket via a single-use link + OTP, outside the public payment flow — approval issues a real ticket immediately (no payment step), rejection sends a rejection email.

**Architecture:** Two new models (`Invitation`, `InvitationRequest`) scoped to `Event`. Admin generates an `Invitation` (ticket type + token + OTP + 7-day expiry). The invitee visits a token URL, enters the OTP (session-gated per the existing `WorkshopBookingController` pattern), fills a fixed form (name/email/phone/influencer category incl. "Other"/Instagram+Facebook+TikTok URL+followers), and submits — creating a `Pending` `InvitationRequest` and marking the `Invitation` `Used`. An admin queue approves (creates a `Ticket` directly in `TicketIssued` status, reusing `TicketQrCode` + `WorkshopBooker::issueKeyFor()` + the existing `TicketIssued` mail) or rejects (new `InvitationRequestRejected` mail).

**Tech Stack:** Laravel 12, PHP 8.3, MySQL (dev) / SQLite in-memory (tests), PHPUnit, Pint, Blade + Alpine.js, existing `Str::random`/`hash_equals` token pattern, existing `throttle` middleware.

**Spec:** `docs/superpowers/specs/2026-09-19-event-invitations-design.md`

## Global Constraints

- Invitations are single-use: one link+OTP admits exactly one person (enforced via a unique FK on `invitation_requests.invitation_id`).
- Invitation expiry is fixed at 7 days from generation (`expires_at = now()->addDays(7)`), matching the existing 7-day payment-link window elsewhere in the app.
- The OTP is a plain 6-digit string, stored unhashed (the admin must be able to view/copy it anytime from the admin list) — do not hash it.
- No payment step: an approved `InvitationRequest` creates a `Ticket` with `status = TicketStatus::TicketIssued` directly (never `PaymentPending`/`Paid`), `is_paid = true`, `payment_method = 'invitation'`.
- Approval reuses the existing `TicketIssued` mail unchanged — do not create a new "invitation approved" mailable.
- The invitation request form has a fixed field set (name, email, phone, influencer category, Instagram/Facebook/TikTok URL+followers) — it does NOT use the per-event configurable `TicketRequestField` system.
- The influencer category select includes the "Other" option and free-text reveal exactly like the ticket-request form (`influencer_category_id = 'other'` stores `influencer_category_other` instead of a FK), and required/optional follows the event's existing `require_influencer_category` setting.
- All three social rows (Instagram/Facebook/TikTok) are always shown with URL + followers on the same row — no per-field admin toggle.
- Visiting an invitation link that is missing, expired, used, or revoked shows one identical static "no longer valid" page — never reveal which of those reasons applies.
- OTP verification is rate-limited via `throttle:5,1` (5 attempts/minute), matching the existing throttle syntax used elsewhere in `routes/web.php`.
- Every PHP file: `declare(strict_types=1);`, explicit return types and param types, curly braces on all control structures (project-wide Boost rule).
- Run `vendor/bin/pint --dirty --format agent` after every PHP/Blade change.
- Run `php artisan migrate --force` against the real dev database after merging (tests run on in-memory SQLite and won't apply migrations there).

---

## File Structure

- `database/migrations/..._create_invitations_table.php` — new `invitations` table.
- `database/migrations/..._create_invitation_requests_table.php` — new `invitation_requests` table.
- `app/Enums/InvitationStatus.php` — `Unused` / `Used` / `Revoked`.
- `app/Enums/InvitationRequestStatus.php` — `Pending` / `Approved` / `Rejected`.
- `app/Models/Invitation.php`, `app/Models/InvitationRequest.php`.
- `database/factories/InvitationFactory.php`, `database/factories/InvitationRequestFactory.php`.
- `app/Models/Event.php` — add `invitations()`/`invitationRequests()` relations.
- `app/Models/Ticket.php` — no change needed (already has `influencer_category_other` from the prior feature).
- `app/Http/Controllers/Admin/InvitationController.php` — admin generate/list/revoke.
- `app/Http/Controllers/Admin/InvitationRequestController.php` — admin review queue (index/updateStatus).
- `app/Http/Controllers/InvitationController.php` — public link landing + OTP verification.
- `app/Http/Controllers/InvitationRequestController.php` — public request form (create/store).
- `app/Http/Requests/Admin/InvitationStoreRequest.php` — validates ticket type choice.
- `app/Http/Requests/InvitationRequestStoreRequest.php` — validates the public form.
- `app/Mail/InvitationRequestReceived.php`, `app/Mail/InvitationRequestRejected.php`.
- `resources/views/emails/invitation-requests/received.blade.php`, `.../rejected.blade.php`.
- `resources/views/admin/invitations/index.blade.php` — generate form + list.
- `resources/views/admin/invitation-requests/index.blade.php` — review queue.
- `resources/views/invitations/verify.blade.php` — OTP entry / "no longer valid" page.
- `resources/views/invitations/create.blade.php` — the public request form.
- `resources/views/admin/partials/sidebar.blade.php` — add two nav entries.
- `routes/web.php` — new public + admin routes.

---

## Task 1: Invitation and InvitationRequest data model

**Files:**
- Create: `app/Enums/InvitationStatus.php`
- Create: `app/Enums/InvitationRequestStatus.php`
- Create: `database/migrations/2026_09_19_150000_create_invitations_table.php`
- Create: `database/migrations/2026_09_19_150001_create_invitation_requests_table.php`
- Create: `app/Models/Invitation.php`
- Create: `app/Models/InvitationRequest.php`
- Create: `database/factories/InvitationFactory.php`
- Create: `database/factories/InvitationRequestFactory.php`
- Modify: `app/Models/Event.php` (add two relations after `sponsorRequests()`, around line 201)
- Test: `tests/Unit/Models/InvitationTest.php`

**Interfaces:**
- Produces: `Invitation` model with `event()`, `ticketType()`, `invitationRequest()` (`HasOne`), casts `status => InvitationStatus::class`, `expires_at => datetime`; helper `isUsable(): bool` (not expired, status `Unused`).
- Produces: `InvitationRequest` model with `invitation()`, `event()`, `influencerCategory()`, `ticket()`, casts `status => InvitationRequestStatus::class`.
- Produces: `Event::invitations(): HasMany`, `Event::invitationRequests(): HasMany`.

- [ ] **Step 1: Write the enums**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Unused = 'unused';
    case Used = 'used';
    case Revoked = 'revoked';
}
```

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

- [ ] **Step 2: Write the migrations**

`database/migrations/2026_09_19_150000_create_invitations_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('token', 40)->unique();
            $table->string('otp', 6);
            $table->string('status')->default('unused')->index();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
```

`database/migrations/2026_09_19_150001_create_invitation_requests_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->foreignId('influencer_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('influencer_category_other')->nullable();
            $table->string('instagram_url')->nullable();
            $table->unsignedInteger('instagram_followers')->nullable();
            $table->string('facebook_url')->nullable();
            $table->unsignedInteger('facebook_followers')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->unsignedInteger('tiktok_followers')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_requests');
    }
};
```

- [ ] **Step 3: Run the migrations against the test connection to verify they apply cleanly**

Run: `php artisan migrate:fresh --env=testing --force` — if the project has no separate testing DB config (tests use in-memory SQLite via `RefreshDatabase`), instead run: `php artisan test --compact --filter=NoSuchTest` (any filter that matches nothing) just to confirm the migration files parse; the real check happens in Step 8's test run.

Expected: no errors, "Nothing to run" or all migrations report DONE.

- [ ] **Step 4: Write the models**

`app/Models/Invitation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invitation extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => InvitationStatus::Unused,
    ];

    protected $fillable = ['event_id', 'ticket_type_id', 'token', 'otp', 'status', 'expires_at'];

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function invitationRequest(): HasOne
    {
        return $this->hasOne(InvitationRequest::class);
    }

    /**
     * Whether this invitation can still be opened and submitted: not used, not revoked, not
     * past its expiry.
     */
    public function isUsable(): bool
    {
        return $this->status === InvitationStatus::Unused && $this->expires_at->isFuture();
    }

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }
}
```

`app/Models/InvitationRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationRequestStatus;
use Database\Factories\InvitationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationRequest extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => InvitationRequestStatus::Pending,
    ];

    protected $fillable = [
        'invitation_id', 'event_id', 'name', 'email', 'phone',
        'influencer_category_id', 'influencer_category_other',
        'instagram_url', 'instagram_followers',
        'facebook_url', 'facebook_followers',
        'tiktok_url', 'tiktok_followers',
        'status', 'ticket_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvitationRequestStatus::class,
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function influencerCategory(): BelongsTo
    {
        return $this->belongsTo(InfluencerCategory::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    protected static function newFactory(): InvitationRequestFactory
    {
        return InvitationRequestFactory::new();
    }
}
```

- [ ] **Step 5: Add the Event relations**

In `app/Models/Event.php`, immediately after the existing `sponsorRequests()` method (around line 201):

```php
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class)->latest();
    }

    public function invitationRequests(): HasMany
    {
        return $this->hasMany(InvitationRequest::class)->latest();
    }
```

(`HasMany` is already imported in this file — confirm the `use Illuminate\Database\Eloquent\Relations\HasMany;` import exists near the top; it does, since `sponsorRequests()` already uses it.)

- [ ] **Step 6: Write the factories**

`database/factories/InvitationFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

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
```

`database/factories/InvitationRequestFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvitationRequestFactory extends Factory
{
    protected $model = InvitationRequest::class;

    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'event_id' => Event::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '+2010'.$this->faker->numerify('#######'),
            'instagram_url' => 'https://instagram.com/'.$this->faker->userName(),
            'instagram_followers' => $this->faker->numberBetween(100, 100000),
            'facebook_url' => 'https://facebook.com/'.$this->faker->userName(),
            'facebook_followers' => $this->faker->numberBetween(100, 100000),
            'tiktok_url' => 'https://tiktok.com/@'.$this->faker->userName(),
            'tiktok_followers' => $this->faker->numberBetween(100, 100000),
        ];
    }
}
```

- [ ] **Step 7: Write the model test**

`tests/Unit/Models/InvitationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unused_unexpired_invitation_is_usable(): void
    {
        $invitation = Invitation::factory()->create([
            'status' => InvitationStatus::Unused,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($invitation->isUsable());
    }

    public function test_an_expired_invitation_is_not_usable(): void
    {
        $invitation = Invitation::factory()->create([
            'status' => InvitationStatus::Unused,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($invitation->isUsable());
    }

    public function test_a_used_invitation_is_not_usable(): void
    {
        $invitation = Invitation::factory()->create([
            'status' => InvitationStatus::Used,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($invitation->isUsable());
    }

    public function test_a_revoked_invitation_is_not_usable(): void
    {
        $invitation = Invitation::factory()->create([
            'status' => InvitationStatus::Revoked,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($invitation->isUsable());
    }
}
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact tests/Unit/Models/InvitationTest.php`
Expected: `4 passed`

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/InvitationStatus.php app/Enums/InvitationRequestStatus.php \
  database/migrations/2026_09_19_150000_create_invitations_table.php \
  database/migrations/2026_09_19_150001_create_invitation_requests_table.php \
  app/Models/Invitation.php app/Models/InvitationRequest.php \
  database/factories/InvitationFactory.php database/factories/InvitationRequestFactory.php \
  app/Models/Event.php tests/Unit/Models/InvitationTest.php
git commit -m "feat: add Invitation and InvitationRequest models"
```

---

## Task 2: Admin — generate, list, and revoke invitations

**Files:**
- Create: `app/Http/Requests/Admin/InvitationStoreRequest.php`
- Create: `app/Http/Controllers/Admin/InvitationController.php`
- Create: `resources/views/admin/invitations/index.blade.php`
- Modify: `routes/web.php` (add routes after the `influencer-categories` block, around line 131)
- Modify: `resources/views/admin/partials/sidebar.blade.php` (add nav entry)
- Test: `tests/Feature/Admin/InvitationCrudTest.php`

**Interfaces:**
- Consumes: `Invitation` model and factory from Task 1; `Event::invitations()`; `Event::ticketTypes()` (existing).
- Produces: routes `admin.events.invitations.index` (GET), `admin.events.invitations.store` (POST), `admin.events.invitations.revoke` (PATCH) — later tasks don't depend on these, but keep names exact for the sidebar link.

- [ ] **Step 1: Write the failing feature test**

`tests/Feature/Admin/InvitationCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_an_invitation(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $response = $this->actingAs($admin)->post(route('admin.events.invitations.store', $event), [
            'ticket_type_id' => $ticketType->id,
        ]);

        $response->assertRedirect(route('admin.events.invitations.index', $event));
        $this->assertDatabaseHas('invitations', [
            'event_id' => $event->id, 'ticket_type_id' => $ticketType->id, 'status' => 'unused',
        ]);

        $invitation = Invitation::where('event_id', $event->id)->firstOrFail();
        $this->assertSame(40, strlen($invitation->token));
        $this->assertSame(6, strlen($invitation->otp));
        $this->assertTrue($invitation->expires_at->isSameDay(now()->addDays(7)));
    }

    public function test_generating_an_invitation_requires_a_ticket_type_from_the_same_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $foreignTicketType = TicketType::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->post(route('admin.events.invitations.store', $event), [
            'ticket_type_id' => $foreignTicketType->id,
        ]);

        $response->assertSessionHasErrors('ticket_type_id');
    }

    public function test_admin_can_view_the_invitations_index(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Invitation::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.invitations.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_revoke_an_unused_invitation(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Unused]);

        $response = $this->actingAs($admin)->patch(route('admin.events.invitations.revoke', [$event, $invitation]));

        $response->assertRedirect(route('admin.events.invitations.index', $event));
        $this->assertSame(InvitationStatus::Revoked, $invitation->fresh()->status);
    }

    public function test_revoking_a_used_invitation_is_a_no_op(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Used]);

        $this->actingAs($admin)->patch(route('admin.events.invitations.revoke', [$event, $invitation]));

        $this->assertSame(InvitationStatus::Used, $invitation->fresh()->status);
    }

    public function test_an_admin_cannot_revoke_an_invitation_from_another_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $invitation = Invitation::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->patch(route('admin.events.invitations.revoke', [$event, $invitation]));

        $response->assertNotFound();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/InvitationCrudTest.php`
Expected: FAIL — routes/controller/request don't exist yet (`RouteNotFoundException` or 404).

- [ ] **Step 3: Write the FormRequest**

`app/Http/Requests/Admin/InvitationStoreRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvitationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'ticket_type_id' => [
                'required',
                'integer',
                Rule::exists('ticket_types', 'id')->where('event_id', $event->id),
            ],
        ];
    }
}
```

- [ ] **Step 4: Write the controller**

`app/Http/Controllers/Admin/InvitationController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvitationStoreRequest;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvitationController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.invitations.index', [
            'event' => $event,
            'invitations' => $event->invitations()->with('ticketType')->get(),
            'ticketTypes' => $event->ticketTypes,
        ]);
    }

    public function store(InvitationStoreRequest $request, Event $event): RedirectResponse
    {
        $invitation = $event->invitations()->create([
            'ticket_type_id' => $request->validated('ticket_type_id'),
            'token' => Str::random(40),
            'otp' => (string) random_int(100000, 999999),
            'status' => InvitationStatus::Unused,
            'expires_at' => now()->addDays(7),
        ]);

        return redirect()
            ->route('admin.events.invitations.index', $event)
            ->with('generated_invitation_id', $invitation->id);
    }

    public function revoke(Event $event, Invitation $invitation): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $invitation);

        if ($invitation->status === InvitationStatus::Unused) {
            $invitation->update(['status' => InvitationStatus::Revoked]);
        }

        return redirect()->route('admin.events.invitations.index', $event);
    }

    private function assertBelongsToEvent(Event $event, Invitation $invitation): void
    {
        if ($invitation->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
```

- [ ] **Step 5: Add the routes**

In `routes/web.php`, add the import near the other `Admin\` imports (alphabetical, after `InfluencerCategoryController`):

```php
use App\Http\Controllers\Admin\InvitationController;
```

Then add, immediately after the `influencer-categories-settings` route (after line 131):

```php
        Route::get('events/{event}/invitations', [InvitationController::class, 'index'])->name('events.invitations.index');
        Route::post('events/{event}/invitations', [InvitationController::class, 'store'])->name('events.invitations.store');
        Route::patch('events/{event}/invitations/{invitation}/revoke', [InvitationController::class, 'revoke'])->name('events.invitations.revoke');
```

- [ ] **Step 6: Write the admin view**

`resources/views/admin/invitations/index.blade.php`:

```blade
{{-- resources/views/admin/invitations/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Invitations').' — '.$event->name_en" />

    @if(session('generated_invitation_id'))
        @php $generated = $invitations->firstWhere('id', session('generated_invitation_id')); @endphp
        @if($generated)
            <div class="mb-6 rounded-2xl border border-hub-purple/30 bg-hub-lavender px-6 py-5">
                <p class="font-display font-bold text-hub-purple mb-3">{{ __('Invitation created — copy these now and send them to the invitee.') }}</p>
                <label class="block text-xs uppercase tracking-wide text-hub-dark/60 mb-1">{{ __('Link') }}</label>
                <input type="text" readonly value="{{ route('invitations.verify', [$event, $generated->token]) }}" class="w-full mb-3 adm-input" onclick="this.select()">
                <label class="block text-xs uppercase tracking-wide text-hub-dark/60 mb-1">{{ __('One-time code') }}</label>
                <input type="text" readonly value="{{ $generated->otp }}" class="w-full adm-input" onclick="this.select()">
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('admin.events.invitations.store', $event) }}" class="mb-8 flex flex-wrap items-end gap-3">
        @csrf
        <x-admin.field type="select" name="ticket_type_id" label="{{ __('Ticket Type') }}" required>
            <option value="" disabled selected>{{ __('Select a ticket type') }}</option>
            @foreach($ticketTypes as $ticketType)
                <option value="{{ $ticketType->id }}">{{ $ticketType->name_en }}</option>
            @endforeach
        </x-admin.field>
        <x-admin.button type="submit">{{ __('Generate Invitation') }}</x-admin.button>
    </form>

    @if($invitations->isEmpty())
        <x-admin.empty-state :message="__('No invitations yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Ticket Type') }}</th>
                    <th>{{ __('OTP') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Expires') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invitations as $invitation)
                    <tr>
                        <td>{{ $invitation->ticketType->name_en }}</td>
                        <td><code>{{ $invitation->otp }}</code></td>
                        <td>
                            @if($invitation->status->value === 'unused' && $invitation->expires_at->isPast())
                                {{ __('Expired') }}
                            @else
                                {{ ucfirst($invitation->status->value) }}
                            @endif
                        </td>
                        <td>{{ $invitation->expires_at->format('Y-m-d') }}</td>
                        <td class="text-end">
                            @if($invitation->status->value === 'unused' && $invitation->expires_at->isFuture())
                                <form method="POST" action="{{ route('admin.events.invitations.revoke', [$event, $invitation]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger">{{ __('Revoke') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
```

Note: `route('invitations.verify', ...)` is defined in Task 3 — this view will error until that route exists, which is fine since this task's own tests don't render the "generated" success panel (no test sets `session('generated_invitation_id')` and asserts on it). If Pint or a manual smoke check of this page fails before Task 3 lands, that's expected; the automated tests in this task don't exercise that branch.

- [ ] **Step 7: Add the sidebar nav entry**

In `resources/views/admin/partials/sidebar.blade.php`, add to the `$eventSections` array (after the `sponsor-requests` entry):

```php
    ['prefix' => 'admin.events.invitations', 'route' => 'admin.events.invitations.index', 'label' => __('Invitations')],
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/InvitationCrudTest.php`
Expected: `6 passed`

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Admin/InvitationStoreRequest.php app/Http/Controllers/Admin/InvitationController.php \
  resources/views/admin/invitations/index.blade.php routes/web.php \
  resources/views/admin/partials/sidebar.blade.php tests/Feature/Admin/InvitationCrudTest.php
git commit -m "feat: let admins generate, list, and revoke invitations"
```

---

## Task 3: Public invitation link + OTP verification

**Files:**
- Create: `app/Http/Controllers/InvitationController.php`
- Create: `resources/views/invitations/verify.blade.php`
- Modify: `routes/web.php` (add public routes inside the `events/{event}` group)
- Test: `tests/Feature/InvitationVerificationTest.php`

**Interfaces:**
- Consumes: `Invitation` model/factory (Task 1); `Event` route-model binding by slug (existing).
- Produces: routes `invitations.verify` (GET show + POST verify, same URI), session key pattern `invitation_verified.{event_id}` storing the verified `Invitation`'s id — Task 4 reads this exact session key.

- [ ] **Step 1: Write the failing feature test**

`tests/Feature/InvitationVerificationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_usable_invitation_shows_the_otp_entry_screen(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create();

        $response = $this->get(route('invitations.verify', [$event, $invitation->token]));

        $response->assertOk();
        $response->assertViewIs('invitations.verify');
        $response->assertViewHas('invalid', false);
    }

    public function test_an_unknown_token_shows_the_invalid_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('invitations.verify', [$event, 'not-a-real-token']));

        $response->assertOk();
        $response->assertViewHas('invalid', true);
    }

    public function test_an_expired_invitation_shows_the_invalid_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['expires_at' => now()->subDay()]);

        $response = $this->get(route('invitations.verify', [$event, $invitation->token]));

        $response->assertViewHas('invalid', true);
    }

    public function test_a_used_invitation_shows_the_invalid_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Used]);

        $response = $this->get(route('invitations.verify', [$event, $invitation->token]));

        $response->assertViewHas('invalid', true);
    }

    public function test_a_revoked_invitation_shows_the_invalid_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Revoked]);

        $response = $this->get(route('invitations.verify', [$event, $invitation->token]));

        $response->assertViewHas('invalid', true);
    }

    public function test_correct_otp_redirects_to_the_request_form_and_sets_session(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['otp' => '123456']);

        $response = $this->post(route('invitations.verify', [$event, $invitation->token]), ['otp' => '123456']);

        $response->assertRedirect(route('invitations.create', [$event, $invitation->token]));
        $this->assertSame($invitation->id, session('invitation_verified.'.$event->id));
    }

    public function test_incorrect_otp_shows_an_error_and_does_not_set_session(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['otp' => '123456']);

        $response = $this->post(route('invitations.verify', [$event, $invitation->token]), ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertNull(session('invitation_verified.'.$event->id));
    }

    public function test_verifying_an_invalid_invitation_is_rejected(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Revoked, 'otp' => '123456']);

        $response = $this->post(route('invitations.verify', [$event, $invitation->token]), ['otp' => '123456']);

        $response->assertViewHas('invalid', true);
        $this->assertNull(session('invitation_verified.'.$event->id));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Feature/InvitationVerificationTest.php`
Expected: FAIL — route `invitations.verify` doesn't exist.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/InvitationController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(Event $event, string $token): View
    {
        $invitation = $this->findUsable($event, $token);

        return view('invitations.verify', [
            'event' => $event,
            'token' => $token,
            'invalid' => $invitation === null,
        ]);
    }

    public function verify(Request $request, Event $event, string $token): RedirectResponse|View
    {
        $invitation = $this->findUsable($event, $token);

        if ($invitation === null) {
            return view('invitations.verify', ['event' => $event, 'token' => $token, 'invalid' => true]);
        }

        $validated = $request->validate(['otp' => ['required', 'string']]);

        if (! hash_equals($invitation->otp, $validated['otp'])) {
            return back()->withErrors(['otp' => __('That code is not correct.')]);
        }

        $request->session()->put($this->sessionKey($event), $invitation->id);

        return redirect()->route('invitations.create', [$event, $token]);
    }

    /**
     * The one place "is this link still good" is decided — every entry point into the
     * invitation flow goes through here so a link cannot be judged differently in two places.
     */
    private function findUsable(Event $event, string $token): ?Invitation
    {
        $invitation = $event->invitations()->where('token', $token)->first();

        return $invitation !== null && $invitation->isUsable() ? $invitation : null;
    }

    private function sessionKey(Event $event): string
    {
        return 'invitation_verified.'.$event->id;
    }
}
```

- [ ] **Step 4: Add the routes**

In `routes/web.php`, add the import (alphabetical, after `HomeController`):

```php
use App\Http\Controllers\InvitationController;
```

Add inside the `events/{event}` public group (after the `newsletter.store` line, before the closing `});` around line 75):

```php
    Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invitations.verify');
    Route::post('/invite/{token}', [InvitationController::class, 'verify'])->middleware('throttle:5,1')->name('invitations.verify.attempt');
```

Note: the GET and POST share the same URI but need distinct route names since both exist. The plan's earlier interface note said "same URI" — use `invitations.verify` for the GET (already referenced by Task 2's view and by this task's own tests for the GET case) and `invitations.verify.attempt` for the POST. **Before writing Step 1's test**, this means `$this->post(route('invitations.verify', ...))` in the test above is WRONG — fix it now: replace every `route('invitations.verify', [$event, $invitation->token])` used with a POST verb in the test file with `route('invitations.verify.attempt', [$event, $invitation->token])`. Re-read the test file and make that substitution in the three POST-based tests (`test_correct_otp_redirects...`, `test_incorrect_otp_shows_an_error...`, `test_verifying_an_invalid_invitation_is_rejected`) before running Step 5.

- [ ] **Step 5: Write the view**

`resources/views/invitations/verify.blade.php`:

```blade
{{-- resources/views/invitations/verify.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Invitation'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        @if($invalid)
            <div class="max-w-xl rounded-2xl border border-white/10 bg-ccs-black px-6 py-8 text-center mx-auto">
                <h1 class="font-display text-2xl font-extrabold mb-3">{{ __('This invitation is no longer valid.') }}</h1>
                <p class="text-gray-400">{{ __('The link may have expired, already been used, or been withdrawn.') }}</p>
            </div>
        @else
            <div class="max-w-md mx-auto">
                <h1 class="font-display text-2xl font-extrabold mb-2">{{ __("You've been invited") }}</h1>
                <p class="text-gray-400 mb-8">{{ __('Enter the one-time code you were given to continue.') }}</p>

                <form method="POST" action="{{ route('invitations.verify.attempt', [$event, $token]) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label for="otp" class="block text-sm text-gray-300 mb-1">{{ __('Invitation code') }}</label>
                        <input id="otp" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2 tracking-widest text-center text-lg">
                        @error('otp') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold transition-opacity">{{ __('Continue') }}</button>
                </form>
            </div>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/InvitationVerificationTest.php`
Expected: `8 passed`

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/InvitationController.php resources/views/invitations/verify.blade.php \
  routes/web.php tests/Feature/InvitationVerificationTest.php
git commit -m "feat: add public invitation link and OTP verification"
```

---

## Task 4: Public invitation request form

**Files:**
- Create: `app/Http/Requests/InvitationRequestStoreRequest.php`
- Create: `app/Http/Controllers/InvitationRequestController.php`
- Create: `app/Mail/InvitationRequestReceived.php`
- Create: `resources/views/emails/invitation-requests/received.blade.php`
- Create: `resources/views/invitations/create.blade.php`
- Modify: `routes/web.php` (add routes inside the `events/{event}` group)
- Test: `tests/Feature/InvitationRequestSubmissionTest.php`

**Interfaces:**
- Consumes: session key `invitation_verified.{event_id}` set by Task 3's `InvitationController::verify`; `InvitationRequest` model/factory (Task 1); `Event::require_influencer_category` (existing column).
- Produces: routes `invitations.create` (GET), `invitations.store` (POST) — Task 5's admin queue reads the `InvitationRequest` rows this creates.

- [ ] **Step 1: Write the failing feature test**

`tests/Feature/InvitationRequestSubmissionTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\InvitationRequestStatus;
use App\Enums\InvitationStatus;
use App\Mail\InvitationRequestReceived;
use App\Models\Event;
use App\Models\InfluencerCategory;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedSession(Event $event, Invitation $invitation): array
    {
        return ['invitation_verified.'.$event->id => $invitation->id];
    }

    public function test_the_form_is_unreachable_without_verifying_the_otp_first(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create();

        $response = $this->get(route('invitations.create', [$event, $invitation->token]));

        $response->assertRedirect(route('invitations.verify', [$event, $invitation->token]));
    }

    public function test_the_form_is_reachable_after_verifying(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create();

        $response = $this->withSession($this->verifiedSession($event, $invitation))
            ->get(route('invitations.create', [$event, $invitation->token]));

        $response->assertOk();
    }

    public function test_submitting_creates_a_pending_invitation_request_and_marks_the_invitation_used(): void
    {
        Mail::fake();
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create();

        $response = $this->withSession($this->verifiedSession($event, $invitation))
            ->post(route('invitations.store', [$event, $invitation->token]), [
                'name' => 'Sara Ali',
                'email' => 'sara@example.com',
                'phone' => '+201001234567',
                'instagram_url' => 'https://instagram.com/sara',
                'instagram_followers' => 5000,
                'facebook_url' => 'https://facebook.com/sara',
                'facebook_followers' => 1200,
                'tiktok_url' => 'https://tiktok.com/@sara',
                'tiktok_followers' => 8000,
            ]);

        $response->assertRedirect(route('landing.show', $event));
        $this->assertDatabaseHas('invitation_requests', [
            'invitation_id' => $invitation->id, 'event_id' => $event->id,
            'name' => 'Sara Ali', 'email' => 'sara@example.com',
            'status' => InvitationRequestStatus::Pending->value,
        ]);
        $this->assertSame(InvitationStatus::Used, $invitation->fresh()->status);
        Mail::assertSent(InvitationRequestReceived::class);
    }

    public function test_submitting_twice_against_the_same_invitation_is_rejected(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $invitation = Invitation::factory()->for($event)->create(['status' => InvitationStatus::Used]);

        $response = $this->withSession($this->verifiedSession($event, $invitation))
            ->post(route('invitations.store', [$event, $invitation->token]), [
                'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
            ]);

        $response->assertRedirect(route('invitations.verify', [$event, $invitation->token]));
        $this->assertDatabaseMissing('invitation_requests', ['invitation_id' => $invitation->id]);
    }

    public function test_other_influencer_category_stores_the_typed_text(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        InfluencerCategory::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->create();

        $this->withSession($this->verifiedSession($event, $invitation))
            ->post(route('invitations.store', [$event, $invitation->token]), [
                'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
                'influencer_category_id' => 'other', 'influencer_category_other' => 'Podcast Host',
            ]);

        $this->assertDatabaseHas('invitation_requests', [
            'email' => 'sara@example.com', 'influencer_category_id' => null, 'influencer_category_other' => 'Podcast Host',
        ]);
    }

    public function test_influencer_category_is_required_when_the_event_requires_it(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published, 'require_influencer_category' => true]);
        InfluencerCategory::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->create();

        $response = $this->withSession($this->verifiedSession($event, $invitation))
            ->post(route('invitations.store', [$event, $invitation->token]), [
                'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
            ]);

        $response->assertSessionHasErrors('influencer_category_id');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Feature/InvitationRequestSubmissionTest.php`
Expected: FAIL — routes don't exist.

- [ ] **Step 3: Write the FormRequest**

`app/Http/Requests/InvitationRequestStoreRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class InvitationRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\'\-\.]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', (new Phone)->international()],
            'influencer_category_id' => [
                $event->require_influencer_category ? 'required' : 'nullable',
                Rule::in(array_merge(
                    ['other'],
                    $event->influencerCategories->pluck('id')->map(fn ($id) => (string) $id)->all(),
                )),
            ],
            'influencer_category_other' => ['nullable', 'required_if:influencer_category_id,other', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'instagram_followers' => ['nullable', 'integer', 'min:0'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'facebook_followers' => ['nullable', 'integer', 'min:0'],
            'tiktok_url' => ['nullable', 'url', 'max:2048'],
            'tiktok_followers' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

- [ ] **Step 4: Write the Mailable**

`app/Mail/InvitationRequestReceived.php`:

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\InvitationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InvitationRequest $invitationRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your request'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-requests.received',
        );
    }
}
```

`resources/views/emails/invitation-requests/received.blade.php`:

```blade
{{-- resources/views/emails/invitation-requests/received.blade.php --}}
@extends('emails.layout')

@section('preview', __('We received your request.'))

@section('content')
    @php $eventName = app()->getLocale() === 'ar' ? $invitationRequest->event->name_ar : $invitationRequest->event->name_en; @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('We received your request.') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $invitationRequest->name }},
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Thank you for your interest in :event. Our team will review your request and email you as soon as a decision is made.', ['event' => $eventName]) }}
    </p>
@endsection
```

- [ ] **Step 5: Write the controller**

`app/Http/Controllers/InvitationRequestController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Http\Requests\InvitationRequestStoreRequest;
use App\Mail\InvitationRequestReceived;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvitationRequestController extends Controller
{
    public function create(Request $request, Event $event, string $token): RedirectResponse|View
    {
        $invitation = $this->verifiedInvitation($request, $event, $token);

        if ($invitation === null) {
            return redirect()->route('invitations.verify', [$event, $token]);
        }

        return view('invitations.create', ['event' => $event, 'token' => $token]);
    }

    public function store(InvitationRequestStoreRequest $request, Event $event, string $token): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $event, $token);

        if ($invitation === null) {
            return redirect()->route('invitations.verify', [$event, $token]);
        }

        $validated = $request->validated();
        $isOtherCategory = ($validated['influencer_category_id'] ?? null) === 'other';

        $invitationRequest = DB::transaction(function () use ($event, $invitation, $validated, $isOtherCategory) {
            $created = $event->invitationRequests()->create([
                'invitation_id' => $invitation->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'influencer_category_id' => $isOtherCategory ? null : ($validated['influencer_category_id'] ?? null),
                'influencer_category_other' => $isOtherCategory ? $validated['influencer_category_other'] : null,
                'instagram_url' => $validated['instagram_url'] ?? null,
                'instagram_followers' => $validated['instagram_followers'] ?? null,
                'facebook_url' => $validated['facebook_url'] ?? null,
                'facebook_followers' => $validated['facebook_followers'] ?? null,
                'tiktok_url' => $validated['tiktok_url'] ?? null,
                'tiktok_followers' => $validated['tiktok_followers'] ?? null,
            ]);

            $invitation->update(['status' => InvitationStatus::Used]);

            return $created;
        });

        $request->session()->forget($this->sessionKey($event));

        try {
            Mail::to($invitationRequest->email)->send(new InvitationRequestReceived($invitationRequest));
        } catch (\Exception $e) {
            Log::error('Failed to send invitation request received email.', [
                'invitation_request_id' => $invitationRequest->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('landing.show', $event)->with('invitation_request_success', true);
    }

    /**
     * The request form and its submission both require the same proof: the OTP for this exact
     * invitation was verified in this session, and the invitation is still usable right now.
     */
    private function verifiedInvitation(Request $request, Event $event, string $token): ?Invitation
    {
        $verifiedId = $request->session()->get($this->sessionKey($event));

        if ($verifiedId === null) {
            return null;
        }

        $invitation = $event->invitations()->where('token', $token)->first();

        if ($invitation === null || $invitation->id !== $verifiedId || ! $invitation->isUsable()) {
            return null;
        }

        return $invitation;
    }

    private function sessionKey(Event $event): string
    {
        return 'invitation_verified.'.$event->id;
    }
}
```

- [ ] **Step 6: Add the routes**

In `routes/web.php`, add the import (alphabetical):

```php
use App\Http\Controllers\InvitationRequestController;
```

Add inside the `events/{event}` public group, right after the two `invitations.verify*` routes added in Task 3:

```php
    Route::get('/invite/{token}/request', [InvitationRequestController::class, 'create'])->name('invitations.create');
    Route::post('/invite/{token}/request', [InvitationRequestController::class, 'store'])->name('invitations.store');
```

- [ ] **Step 7: Write the request-form view**

`resources/views/invitations/create.blade.php`:

```blade
{{-- resources/views/invitations/create.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Request Your Ticket'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24" x-data="{ influencerCategoryId: '{{ old('influencer_category_id') }}' }">
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-6 max-w-2xl" data-reveal>{{ __('Request Your Ticket') }}</h1>

        <form method="POST" action="{{ route('invitations.store', [$event, $token]) }}" class="max-w-xl flex flex-col gap-4">
            @csrf

            <div>
                <label for="name" class="block text-sm text-gray-300 mb-1">{{ __('Name') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. Ahmed Hassan') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm text-gray-300 mb-1">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('name@example.com') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm text-gray-300 mb-1">{{ __('Phone') }}</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                @error('phone') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            @if($event->influencerCategories->isNotEmpty())
                <div>
                    <label for="influencer_category_id" class="block text-sm text-gray-300 mb-1">
                        {{ __('Influencer Category') }}
                        @unless($event->require_influencer_category)
                            <span class="text-gray-500">({{ __('optional') }})</span>
                        @endunless
                    </label>
                    <x-nice-select
                        id="influencer_category_id"
                        name="influencer_category_id"
                        model="influencerCategoryId"
                        :options="$event->influencerCategories->map(fn ($category) => [
                            'value' => (string) $category->id,
                            'label' => app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en,
                        ])->push(['value' => 'other', 'label' => __('Other')])->values()->all()"
                    />
                    @error('influencer_category_id') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror

                    <div x-show="influencerCategoryId === 'other'" x-cloak class="mt-3">
                        <input type="text" name="influencer_category_other" value="{{ old('influencer_category_other') }}" placeholder="{{ __('Tell us your category') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('influencer_category_other') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="instagram_url" class="block text-sm text-gray-300 mb-1">{{ __('Instagram profile URL') }}</label>
                    <input id="instagram_url" type="url" name="instagram_url" value="{{ old('instagram_url') }}" placeholder="https://instagram.com/" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('instagram_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="instagram_followers" class="block text-sm text-gray-300 mb-1">{{ __('Followers') }}</label>
                    <input id="instagram_followers" type="number" min="0" name="instagram_followers" value="{{ old('instagram_followers') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('instagram_followers') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="facebook_url" class="block text-sm text-gray-300 mb-1">{{ __('Facebook profile URL') }}</label>
                    <input id="facebook_url" type="url" name="facebook_url" value="{{ old('facebook_url') }}" placeholder="https://facebook.com/" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('facebook_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="facebook_followers" class="block text-sm text-gray-300 mb-1">{{ __('Followers') }}</label>
                    <input id="facebook_followers" type="number" min="0" name="facebook_followers" value="{{ old('facebook_followers') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('facebook_followers') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="tiktok_url" class="block text-sm text-gray-300 mb-1">{{ __('TikTok profile URL') }}</label>
                    <input id="tiktok_url" type="url" name="tiktok_url" value="{{ old('tiktok_url') }}" placeholder="https://tiktok.com/@" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('tiktok_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tiktok_followers" class="block text-sm text-gray-300 mb-1">{{ __('Followers') }}</label>
                    <input id="tiktok_followers" type="number" min="0" name="tiktok_followers" value="{{ old('tiktok_followers') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('tiktok_followers') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold transition-opacity">{{ __('Submit Request') }}</button>
        </form>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact tests/Feature/InvitationRequestSubmissionTest.php`
Expected: `6 passed`

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/InvitationRequestStoreRequest.php app/Http/Controllers/InvitationRequestController.php \
  app/Mail/InvitationRequestReceived.php resources/views/emails/invitation-requests/received.blade.php \
  resources/views/invitations/create.blade.php routes/web.php \
  tests/Feature/InvitationRequestSubmissionTest.php
git commit -m "feat: add the public invitation request form"
```

---

## Task 5: Admin review queue — approve issues a ticket, reject sends an email

**Files:**
- Create: `app/Http/Controllers/Admin/InvitationRequestController.php`
- Create: `app/Mail/InvitationRequestRejected.php`
- Create: `resources/views/emails/invitation-requests/rejected.blade.php`
- Create: `resources/views/admin/invitation-requests/index.blade.php`
- Modify: `routes/web.php` (admin routes)
- Modify: `resources/views/admin/partials/sidebar.blade.php` (second nav entry)
- Test: `tests/Feature/Admin/InvitationRequestQueueTest.php`

**Interfaces:**
- Consumes: `InvitationRequest` model (Task 1), `TicketQrCode::pngFor(Ticket): ?string` (existing, `app/Services/TicketQrCode.php` — returns null only if the ticket's `ticket_id` secret is null), `WorkshopBooker::issueKeyFor(Ticket): ?string` (existing, `app/Services/WorkshopBooker.php`), `App\Mail\TicketIssued` (existing, constructor is `__construct(public Ticket $ticket, public string $qrImage)` — **`$qrImage` is non-nullable `string`**, confirmed by reading `app/Mail/TicketIssued.php:19`), `App\Enums\TicketStatus::TicketIssued` (existing).
- Produces: routes `admin.events.invitation-requests.index`, `admin.events.invitation-requests.update-status`.

- [ ] **Step 1: Write the failing feature test**

`tests/Feature/Admin/InvitationRequestQueueTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\InvitationRequestStatus;
use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Mail\InvitationRequestRejected;
use App\Models\Event;
use App\Models\InvitationRequest;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_queue(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        InvitationRequest::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.invitation-requests.index', $event));

        $response->assertOk();
    }

    public function test_approving_creates_a_ticket_issued_directly_and_sends_ticket_issued_mail(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['price' => 500]);
        $invitationRequest = InvitationRequest::factory()->for($event)->create();
        $invitationRequest->invitation()->update(['ticket_type_id' => $ticketType->id]);

        $response = $this->actingAs($admin)->patch(
            route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved'])
        );

        $response->assertRedirect(route('admin.events.invitation-requests.index', $event));
        $invitationRequest->refresh();
        $this->assertSame(InvitationRequestStatus::Approved, $invitationRequest->status);
        $this->assertNotNull($invitationRequest->ticket_id);

        $ticket = $invitationRequest->ticket;
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $this->assertTrue((bool) $ticket->is_paid);
        $this->assertSame('invitation', $ticket->payment_method);
        $this->assertSame($ticketType->id, $ticket->ticket_type_id);
        $this->assertSame($invitationRequest->name, $ticket->name);
        $this->assertSame($invitationRequest->email, $ticket->email);

        Mail::assertSent(TicketIssued::class);
    }

    public function test_rejecting_sends_a_rejection_email_and_creates_no_ticket(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $invitationRequest = InvitationRequest::factory()->for($event)->create();

        $response = $this->actingAs($admin)->patch(
            route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'rejected'])
        );

        $response->assertRedirect(route('admin.events.invitation-requests.index', $event));
        $invitationRequest->refresh();
        $this->assertSame(InvitationRequestStatus::Rejected, $invitationRequest->status);
        $this->assertNull($invitationRequest->ticket_id);
        Mail::assertSent(InvitationRequestRejected::class);
    }

    public function test_an_admin_cannot_update_a_request_from_another_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $invitationRequest = InvitationRequest::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->patch(
            route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved'])
        );

        $response->assertNotFound();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/InvitationRequestQueueTest.php`
Expected: FAIL — routes/controller/mailable don't exist yet.

- [ ] **Step 3: Write the rejection Mailable**

`app/Mail/InvitationRequestRejected.php`:

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\InvitationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InvitationRequest $invitationRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('About your request'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-requests.rejected',
        );
    }
}
```

`resources/views/emails/invitation-requests/rejected.blade.php`:

```blade
{{-- resources/views/emails/invitation-requests/rejected.blade.php --}}
@extends('emails.layout')

@section('preview', __('About your request.'))

@section('content')
    @php $eventName = app()->getLocale() === 'ar' ? $invitationRequest->event->name_ar : $invitationRequest->event->name_en; @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('About your request') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $invitationRequest->name }},
    </p>
    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Thank you for your interest in :event. We are not able to approve your request this time.', ['event' => $eventName]) }}
    </p>
@endsection
```

- [ ] **Step 4: Write the controller**

`app/Http/Controllers/Admin/InvitationRequestController.php` — `pngFor()` returns `?string` but is only ever null when the ticket's `ticket_id` is null; since `ticket_id` is always set inside the transaction below before this is called, `$qrImage` is guaranteed non-null in this path and can be passed directly to `TicketIssued`'s non-nullable `string $qrImage` parameter without a null check:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvitationRequestStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\InvitationRequestRejected;
use App\Mail\TicketIssued;
use App\Models\Event;
use App\Models\InvitationRequest;
use App\Services\TicketQrCode;
use App\Services\WorkshopBooker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvitationRequestController extends Controller
{
    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', InvitationRequestStatus::Pending->value);

        $invitationRequests = $event->invitationRequests()
            ->with(['invitation.ticketType', 'influencerCategory'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->get();

        return view('admin.invitation-requests.index', [
            'event' => $event,
            'invitationRequests' => $invitationRequests,
            'status' => $status,
        ]);
    }

    public function updateStatus(Event $event, InvitationRequest $invitationRequest, string $status): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $invitationRequest);

        $status = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'in:approved,rejected']],
        )->validate()['status'];

        if ($status === 'rejected') {
            try {
                Mail::to($invitationRequest->email)->send(new InvitationRequestRejected($invitationRequest));
                $invitationRequest->update(['status' => InvitationRequestStatus::Rejected]);

                return redirect()
                    ->route('admin.events.invitation-requests.index', $event)
                    ->with('success', __('Request rejected successfully.'));
            } catch (\Exception $e) {
                Log::error('Failed to send invitation rejection email.', [
                    'invitation_request_id' => $invitationRequest->id,
                    'exception' => $e,
                ]);

                return redirect()
                    ->route('admin.events.invitation-requests.index', $event)
                    ->with('error', __('The request status was not changed because the email could not be sent.'));
            }
        }

        $ticket = DB::transaction(function () use ($event, $invitationRequest) {
            $invitation = $invitationRequest->invitation;

            $ticket = $event->tickets()->create([
                'ticket_type_id' => $invitation->ticket_type_id,
                'influencer_category_id' => $invitationRequest->influencer_category_id,
                'influencer_category_other' => $invitationRequest->influencer_category_other,
                'price' => $invitation->ticketType->price,
                'discount_amount' => 0,
                'name' => $invitationRequest->name,
                'email' => $invitationRequest->email,
                'phone' => $invitationRequest->phone,
                'status' => TicketStatus::TicketIssued,
                'is_paid' => true,
                'payment_method' => 'invitation',
            ]);

            $ticket->update(['ticket_number' => strtoupper(str_replace('-', '', $event->slug)).'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticket->update(['ticket_id' => \Illuminate\Support\Str::random(40)]);

            (new WorkshopBooker)->issueKeyFor($ticket->fresh('ticketType'));
            $ticket->refresh();

            $invitationRequest->update(['ticket_id' => $ticket->id]);

            return $ticket;
        });

        // ticket_id is always set above before this runs, so pngFor() cannot return null here —
        // the assertion turns that invariant into a type the non-nullable TicketIssued accepts.
        $qrImage = (new TicketQrCode)->pngFor($ticket);
        assert($qrImage !== null);

        try {
            Mail::to($ticket->email)->send(new TicketIssued($ticket, $qrImage));
            $invitationRequest->update(['status' => InvitationRequestStatus::Approved]);

            return redirect()
                ->route('admin.events.invitation-requests.index', $event)
                ->with('success', __('Request approved and ticket issued.'));
        } catch (\Exception $e) {
            Log::error('Failed to send ticket issued email for an invitation.', [
                'invitation_request_id' => $invitationRequest->id,
                'exception' => $e,
            ]);

            return redirect()
                ->route('admin.events.invitation-requests.index', $event)
                ->with('error', __('The ticket was created but the email could not be sent.'));
        }
    }

    private function assertBelongsToEvent(Event $event, InvitationRequest $invitationRequest): void
    {
        if ($invitationRequest->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
```

- [ ] **Step 6: Add the routes**

In `routes/web.php`, add the import with an alias (matching the existing `SponsorRequestController as AdminSponsorRequestController` convention):

```php
use App\Http\Controllers\Admin\InvitationRequestController as AdminInvitationRequestController;
```

Add after the invitations routes from Task 2:

```php
        Route::get('events/{event}/invitation-requests', [AdminInvitationRequestController::class, 'index'])->name('events.invitation-requests.index');
        Route::patch('events/{event}/invitation-requests/{invitationRequest}/{status}', [AdminInvitationRequestController::class, 'updateStatus'])
            ->name('events.invitation-requests.update-status');
```

- [ ] **Step 7: Write the admin queue view**

`resources/views/admin/invitation-requests/index.blade.php`:

```blade
{{-- resources/views/admin/invitation-requests/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Invitation Requests').' — '.$event->name_en" />

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex gap-2 text-sm">
        @foreach(['pending', 'approved', 'rejected', 'all'] as $option)
            <a href="{{ route('admin.events.invitation-requests.index', $event) }}?status={{ $option }}"
               class="px-3 py-1.5 rounded {{ $status === $option ? 'bg-hub-purple text-hub-dark' : 'border border-hub-purple/20 text-hub-dark/75' }}">
                {{ ucfirst($option) }}
            </a>
        @endforeach
    </div>

    @if($invitationRequests->isEmpty())
        <x-admin.empty-state :message="__('No invitation requests yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Ticket Type') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Social') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invitationRequests as $invitationRequest)
                    <tr class="align-top">
                        <td>{{ $invitationRequest->name }}</td>
                        <td>{{ $invitationRequest->email }}</td>
                        <td>{{ $invitationRequest->phone }}</td>
                        <td>{{ $invitationRequest->invitation->ticketType->name_en }}</td>
                        <td>{{ $invitationRequest->influencerCategory->name_en ?? $invitationRequest->influencer_category_other }}</td>
                        <td>
                            @if($invitationRequest->instagram_url)
                                <a href="{{ $invitationRequest->instagram_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('Instagram') }} ({{ $invitationRequest->instagram_followers }})</a>
                            @endif
                            @if($invitationRequest->facebook_url)
                                <a href="{{ $invitationRequest->facebook_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('Facebook') }} ({{ $invitationRequest->facebook_followers }})</a>
                            @endif
                            @if($invitationRequest->tiktok_url)
                                <a href="{{ $invitationRequest->tiktok_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('TikTok') }} ({{ $invitationRequest->tiktok_followers }})</a>
                            @endif
                        </td>
                        <td>{{ ucfirst($invitationRequest->status->value) }}</td>
                        <td class="text-end">
                            @if($invitationRequest->status === \App\Enums\InvitationRequestStatus::Pending)
                                <form method="POST" action="{{ route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit">{{ __('Approve') }}</x-admin.button>
                                </form>
                                <form method="POST" action="{{ route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'rejected']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Reject') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
```

- [ ] **Step 8: Add the second sidebar nav entry**

In `resources/views/admin/partials/sidebar.blade.php`, add to `$eventSections` (after the `invitations` entry added in Task 2):

```php
    ['prefix' => 'admin.events.invitation-requests', 'route' => 'admin.events.invitation-requests.index', 'label' => __('Invitation Requests')],
```

- [ ] **Step 9: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/InvitationRequestQueueTest.php`
Expected: `4 passed`

- [ ] **Step 10: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/InvitationRequestController.php app/Mail/InvitationRequestRejected.php \
  resources/views/emails/invitation-requests/rejected.blade.php resources/views/admin/invitation-requests/index.blade.php \
  routes/web.php resources/views/admin/partials/sidebar.blade.php \
  tests/Feature/Admin/InvitationRequestQueueTest.php
git commit -m "feat: add the invitation-request review queue with ticket issuance"
```

---

## Task 6: Full-suite verification and dev database migration

**Files:** none created; this task only verifies and migrates.

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all tests pass (previous total was 626; this plan adds roughly 4 + 6 + 8 + 6 + 4 = 28 new tests, so expect approximately 654 passed).

- [ ] **Step 2: Run Pint across the whole diff one more time**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Apply the new migrations to the real dev database**

Run: `php artisan migrate --force`
Expected: both `create_invitations_table` and `create_invitation_requests_table` report DONE.

- [ ] **Step 4: Smoke-check the admin nav renders**

Run: `php artisan route:list --name=invitations` and `php artisan route:list --name=invitation-requests`
Expected: all 8 new named routes listed (`invitations.verify`, `invitations.verify.attempt`, `invitations.create`, `invitations.store`, `admin.events.invitations.index/store/revoke`, `admin.events.invitation-requests.index/update-status`).

- [ ] **Step 5: Final commit if anything was adjusted during verification**

```bash
git add -A
git commit -m "chore: verify Event Invitations feature end to end"
```

(Skip this commit if Steps 1-4 required no code changes.)

---

## Self-Review Notes

- **Spec coverage**: admin generate/list/revoke (Task 2), public token+OTP gate with rate limiting and the uniform "invalid" page (Task 3), fixed request form with Other-category and all three social rows (Task 4), approve→real ticket with QR+workshop key+existing `TicketIssued` mail, reject→new mail (Task 5), single-use enforcement via the unique `invitation_id` FK plus the `isUsable()`/session re-check in Task 4 (test: `test_submitting_twice_against_the_same_invitation_is_rejected`), cross-event guards on both admin controllers (tests in Tasks 2 and 5) — all covered.
- **Placeholder scan**: no TBD/TODO; `TicketIssued`'s exact constructor signature was confirmed by reading `app/Mail/TicketIssued.php:19` while writing this plan (`string $qrImage`, non-nullable), and Task 5's controller code includes the `assert($qrImage !== null)` narrowing this requires — no unresolved guesses remain.
- **Type consistency**: `Invitation::isUsable()` (Task 1) is used identically in `InvitationController::findUsable` (Task 3) and `InvitationRequestController::verifiedInvitation` (Task 4). Session key `'invitation_verified.'.$event->id` is defined identically in both controllers (Tasks 3 and 4) — if this key ever changes, both call sites must change together; this is flagged here since it's the one piece of state shared across files without a shared constant. `InvitationRequest::$fillable` (Task 1) lists every column `InvitationRequestController::store` (Task 4) and `Admin\InvitationRequestController::updateStatus` (Task 5) write to.

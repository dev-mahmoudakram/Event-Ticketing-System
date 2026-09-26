# Agenda and Workshops Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sessions and workshops with several ordered speakers, admin-managed session types and locations, start–end times, rich descriptions, one merged public schedule, and career-180-style cards with a details pop-up.

**Architecture:** Two new per-event lists (`session_types`, `locations`) and two ordered speaker pivots. `agenda_items` swap their fixed `type`/`speaker_id`/`workshop_id` for `session_type_id`, `location_id` and a description; workshops gain a schedule, a location and the speaker pivot. A read-only `EventSchedule` service merges sessions and scheduled workshops per day; one `x-schedule-card` component plus one Alpine `schedulePopup` render every public card and its pop-up.

**Tech Stack:** Laravel 12, PHP 8.3, MySQL (dev/live) + in-memory SQLite (tests), Blade + Alpine, Tailwind v4 + SCSS, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-09-27-agenda-workshops-redesign-design.md`

## Global Constraints

- No new Composer or npm dependencies.
- Event palette only: coral, gold, maroon, red, black, white/grey — no teal (a test enforces it).
- Every admin route added here must resolve to `Permission::AgendaWorkshops` (the route-coverage test in `tests/Feature/Admin/PermissionAccessTest.php` enforces it).
- Every user-visible string goes through `__()` with an Arabic entry in `lang/ar.json`.
- Migrations reference old values as plain strings (`'keynote'`, …), never `App\Enums\AgendaItemType`.
- Rich text uses the existing `App\Casts\SanitizedRichText` cast and is rendered with `{!! !!}` only through that cast.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes; tests with `php artisan test --compact`.
- Commits under the user's git identity, no `Co-Authored-By` line.
- In this Git Bash environment heredocs strip backslashes: write PHP/Python/JS files with the file-writing tool.

## Review Focus

1. **A day with sessions and workshops at the same start time** must list both, sessions first, stable across page loads — Task 4 test `test_entries_at_the_same_time_list_sessions_before_workshops`.
2. **A workshop with no schedule yet** must not appear on the agenda and must not break the workshops page — Task 4 test `test_unscheduled_workshops_stay_off_the_agenda` and Task 5 test `test_an_unscheduled_workshop_still_shows_on_the_workshops_page`.
3. **Deleting a type or location that is in use** must be refused, and a location used only by a workshop still counts as in use — Task 1 test `test_a_location_used_only_by_a_workshop_cannot_be_deleted`.
4. **A speaker removed from the event** (speaker deleted) must drop off sessions and workshops without breaking cards — Task 2 test `test_deleting_a_speaker_removes_them_from_sessions_and_workshops`.
5. **A deep link to a session that no longer exists** (`#session-999`) must just show the agenda with no pop-up and no JS error — Task 4 browser check step, plus test `test_every_card_has_a_matching_detail_template` guarding the id pairing.

---

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/*_create_session_types_and_locations_tables.php` (new) | The two per-event lists; seeds the five default types for existing events |
| `database/migrations/*_restructure_agenda_items_and_workshops.php` (new) | Pivots, new columns, data move, drop old columns |
| `app/Models/SessionType.php`, `app/Models/Location.php` (new) + factories | The lists; `SessionType::seedDefaultsFor()` |
| `app/Models/Concerns/HasOrderedSpeakers.php` (new) | `syncSpeakersInOrder()` shared by sessions and workshops |
| `app/Models/AgendaItem.php`, `app/Models/Workshop.php`, `app/Models/Event.php` | New relations, casts, fillable; event seeds default types on create |
| `app/Http/Controllers/Admin/SessionTypeController.php`, `LocationController.php` (new) | List CRUD + reorder |
| `app/Http/Requests/Admin/ScheduleListRequest.php` (new) | Shared validation for both lists |
| `resources/views/admin/schedule-lists/index.blade.php`, `form.blade.php` (new) | Shared views for both lists |
| `resources/views/components/admin/speaker-picker.blade.php` (new) | Ordered multi-speaker picker |
| `app/Http/Requests/Admin/AgendaItemRequest.php`, `WorkshopRequest.php` | New validation |
| `app/Http/Controllers/Admin/AgendaItemController.php`, `WorkshopController.php` | Save speakers; pass lists to forms |
| `resources/views/admin/agenda-items/*`, `resources/views/admin/workshops/form.blade.php` | New fields |
| `app/Support/ScheduleEntry.php`, `app/Services/EventSchedule.php` (new) | One entry per card; merged per-day schedule |
| `resources/views/components/speaker-avatar.blade.php`, `schedule-card.blade.php`, `schedule-popup.blade.php` (new) | Public card, avatar, pop-up |
| `resources/js/schedule-popup.js` (new) | Alpine pop-up: open/close, deep links, focus |
| `app/Http/Controllers/AgendaController.php`, `resources/views/agenda/show.blade.php` | New agenda page |
| `resources/views/landing/partials/workshops-teaser.blade.php`, `resources/views/workshops/index.blade.php`, `show.blade.php`, `app/Http/Controllers/WorkshopController.php` | Workshops use the card/pop-up |
| `app/Enums/Permission.php`, `resources/views/admin/partials/sidebar.blade.php` | New routes mapped; sidebar links |
| `database/seeders/CcsEventSeeder.php` | New structure |
| `app/Enums/AgendaItemType.php` | **Deleted** |

---

### Task 1: Session types and locations

**Files:**
- Create: migration `create_session_types_and_locations_tables`, `app/Models/SessionType.php`, `app/Models/Location.php`, `database/factories/SessionTypeFactory.php`, `database/factories/LocationFactory.php`
- Create: `app/Http/Controllers/Admin/SessionTypeController.php`, `app/Http/Controllers/Admin/LocationController.php`, `app/Http/Requests/Admin/ScheduleListRequest.php`, `resources/views/admin/schedule-lists/index.blade.php`, `resources/views/admin/schedule-lists/form.blade.php`
- Modify: `app/Models/Event.php`, `app/Enums/Permission.php`, `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php`, `lang/*.json`
- Test: `tests/Feature/Admin/ScheduleListsTest.php` (new), `tests/Unit/AdminPermissionsTest.php` (add rows)

**Interfaces:**
- Produces: `SessionType` (`event_id`, `name_ar`, `name_en`, `is_break` bool, `sort_order`; `name(): string`; `static seedDefaultsFor(Event): void`; `agendaItems(): HasMany`), `Location` (`event_id`, `name_ar`, `name_en`, `sort_order`; `name(): string`; `agendaItems(): HasMany`, `workshops(): HasMany`), `Event::sessionTypes()`, `Event::locations()` (ordered by `sort_order`). The `agendaItems()`/`workshops()` relations on Location/SessionType reference `session_type_id`/`location_id` columns that Task 2 adds — until then, usage counts are 0 and the in-use guard is exercised in Task 2's tests.
- Routes: `admin.events.session-types.{index,create,store,edit,update,destroy,reorder}` (param `sessionType`), `admin.events.locations.{…,reorder}` (param `location`).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Admin/ScheduleListsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Location;
use App\Models\SessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_event_gets_the_five_default_session_types(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(['Keynote', 'Session', 'Workshop', 'Break', 'Panel'], $event->sessionTypes()->pluck('name_en')->all());
        $this->assertTrue($event->sessionTypes()->where('name_en', 'Break')->sole()->is_break);
        $this->assertSame(1, $event->sessionTypes()->where('is_break', true)->count());
    }

    public function test_an_admin_adds_edits_and_deletes_a_session_type(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.session-types.store', $event), [
            'name_ar' => 'جلسة حوارية', 'name_en' => 'Fireside chat',
        ])->assertRedirect(route('admin.events.session-types.index', $event));
        $type = $event->sessionTypes()->where('name_en', 'Fireside chat')->sole();
        $this->assertSame(5, $type->sort_order);

        $this->actingAs($admin)->put(route('admin.events.session-types.update', [$event, $type]), [
            'name_ar' => 'جلسة حوارية', 'name_en' => 'Fireside', 'is_break' => '1',
        ])->assertRedirect(route('admin.events.session-types.index', $event));
        $this->assertTrue($type->fresh()->is_break);

        $this->actingAs($admin)->delete(route('admin.events.session-types.destroy', [$event, $type]))
            ->assertRedirect(route('admin.events.session-types.index', $event));
        $this->assertModelMissing($type);
    }

    public function test_an_admin_manages_locations(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.locations.store', $event), [
            'name_ar' => 'المسرح', 'name_en' => 'Main Stage',
        ])->assertRedirect(route('admin.events.locations.index', $event));

        $this->actingAs($admin)->get(route('admin.events.locations.index', ['event' => $event, 'lang' => 'en']))
            ->assertOk()->assertSee('Main Stage');
    }

    public function test_names_are_required_in_both_languages(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.locations.store', Event::factory()->create()), ['name_ar' => '', 'name_en' => ''])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_lists_reorder(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ids = $event->sessionTypes()->pluck('id')->reverse()->values()->all();

        $this->actingAs($admin)->postJson(route('admin.events.session-types.reorder', $event), ['ids' => $ids])->assertOk();

        $this->assertSame($ids, $event->sessionTypes()->pluck('id')->all());
    }

    public function test_another_events_rows_cannot_be_reached(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $foreign = Location::factory()->create();

        $this->actingAs($admin)->get(route('admin.events.locations.edit', [$event, $foreign]))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.events.locations.destroy', [$event, $foreign]))->assertNotFound();
        $this->assertModelExists($foreign);

        $foreignType = SessionType::factory()->create();
        $this->actingAs($admin)->postJson(route('admin.events.session-types.reorder', $event), ['ids' => [$foreignType->id]])
            ->assertUnprocessable();
    }
}
```

Add to `mappedRoutes()` in `tests/Unit/AdminPermissionsTest.php`:

```php
            'session types' => ['admin.events.session-types.reorder', Permission::AgendaWorkshops],
            'locations' => ['admin.events.locations.edit', Permission::AgendaWorkshops],
```

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --compact tests/Feature/Admin/ScheduleListsTest.php tests/Unit/AdminPermissionsTest.php`
Expected: FAIL — `Call to undefined method App\Models\Event::sessionTypes()` and the two permission rows resolve to null.

- [ ] **Step 3: Migration**

`php artisan make:migration create_session_types_and_locations_tables --no-interaction`, then:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-event lists the agenda picks from: session types (with the five the old fixed list had)
 * and locations. New events get the default types from SessionType::seedDefaultsFor(); existing
 * events get them here, written out so this migration doesn't depend on the model.
 */
return new class extends Migration
{
    /** @var list<array{string, string, bool}> */
    private const DEFAULT_TYPES = [
        ['كلمة رئيسية', 'Keynote', false],
        ['جلسة', 'Session', false],
        ['ورشة عمل', 'Workshop', false],
        ['استراحة', 'Break', true],
        ['حلقة نقاشية', 'Panel', false],
    ];

    public function up(): void
    {
        Schema::create('session_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            // Sessions of a break type show as a slim line on the agenda.
            $table->boolean('is_break')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach (DB::table('events')->pluck('id') as $eventId) {
            foreach (self::DEFAULT_TYPES as $position => [$nameAr, $nameEn, $isBreak]) {
                DB::table('session_types')->insert([
                    'event_id' => $eventId, 'name_ar' => $nameAr, 'name_en' => $nameEn,
                    'is_break' => $isBreak, 'sort_order' => $position, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('session_types');
    }
};
```

- [ ] **Step 4: Models and factories**

`php artisan make:model SessionType --factory --no-interaction` and `php artisan make:model Location --factory --no-interaction`, then replace.

`app/Models/SessionType.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SessionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of agenda session ("Keynote", "Fireside chat"), per event and bilingual. Shown as the
 * pill on each agenda card; a break type renders as a slim line instead of a card.
 */
class SessionType extends Model
{
    /** @use HasFactory<SessionTypeFactory> */
    use HasFactory;

    /** @var list<array{name_ar: string, name_en: string, is_break: bool}> */
    public const DEFAULTS = [
        ['name_ar' => 'كلمة رئيسية', 'name_en' => 'Keynote', 'is_break' => false],
        ['name_ar' => 'جلسة', 'name_en' => 'Session', 'is_break' => false],
        ['name_ar' => 'ورشة عمل', 'name_en' => 'Workshop', 'is_break' => false],
        ['name_ar' => 'استراحة', 'name_en' => 'Break', 'is_break' => true],
        ['name_ar' => 'حلقة نقاشية', 'name_en' => 'Panel', 'is_break' => false],
    ];

    protected $fillable = ['event_id', 'name_ar', 'name_en', 'is_break', 'sort_order'];

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }

    public static function seedDefaultsFor(Event $event): void
    {
        foreach (self::DEFAULTS as $position => $type) {
            $event->sessionTypes()->create($type + ['sort_order' => $position]);
        }
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }
}
```

`app/Models/Location.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Where a session or workshop happens ("Main Stage", "Room A"), per event and bilingual.
 */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected $fillable = ['event_id', 'name_ar', 'name_en', 'sort_order'];

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }
}
```

`database/factories/SessionTypeFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\SessionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionType>
 */
class SessionTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => 'نوع '.$this->faker->unique()->numberBetween(1, 99999),
            'name_en' => ucfirst($this->faker->unique()->words(2, true)),
            'is_break' => false,
            'sort_order' => 10,
        ];
    }
}
```

`database/factories/LocationFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name_ar' => 'قاعة '.$this->faker->unique()->numberBetween(1, 999),
            'name_en' => 'Room '.$this->faker->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}
```

`app/Models/Event.php` — add the relations next to `workshops()`:

```php
    public function sessionTypes(): HasMany
    {
        return $this->hasMany(SessionType::class)->orderBy('sort_order')->orderBy('id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderBy('sort_order')->orderBy('id');
    }
```

and seed the defaults on create — if `Event` already has a `booted()` method add the line inside it, otherwise add:

```php
    protected static function booted(): void
    {
        // Every event starts with the agenda types the old fixed list had.
        static::created(fn (Event $event) => SessionType::seedDefaultsFor($event));
    }
```

- [ ] **Step 5: Request, controllers, views**

`app/Http/Requests/Admin/ScheduleListRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Session types and locations: a bilingual name each; types also say whether they are a break.
 */
class ScheduleListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'is_break' => ['nullable', 'boolean'],
        ];
    }
}
```

`app/Http/Controllers/Admin/SessionTypeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleListRequest;
use App\Models\Event;
use App\Models\SessionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SessionTypeController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.schedule-lists.index', $this->screen($event) + [
            'items' => $event->sessionTypes()->withCount('agendaItems')->get()
                ->each(fn (SessionType $type) => $type->setAttribute('usage_count', $type->agenda_items_count)),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => new SessionType]);
    }

    public function store(ScheduleListRequest $request, Event $event): RedirectResponse
    {
        $event->sessionTypes()->create($request->safe()->only(['name_ar', 'name_en']) + [
            'is_break' => $request->boolean('is_break'),
            'sort_order' => (int) $event->sessionTypes()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, SessionType $sessionType): View
    {
        $this->assertBelongsToEvent($event, $sessionType);

        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => $sessionType]);
    }

    public function update(ScheduleListRequest $request, Event $event, SessionType $sessionType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sessionType);
        $sessionType->update($request->safe()->only(['name_ar', 'name_en']) + ['is_break' => $request->boolean('is_break')]);

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, SessionType $sessionType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sessionType);
        $inUse = $sessionType->agendaItems()->count();

        if ($inUse > 0) {
            return redirect()->route('admin.events.session-types.index', $event)
                ->with('error', trans_choice('In use by :count session — move it first.|In use by :count sessions or workshops — move them first.', $inUse, ['count' => $inUse]));
        }

        $sessionType->delete();

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('session_types', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            SessionType::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** @return array<string, mixed> */
    private function screen(Event $event): array
    {
        return [
            'event' => $event,
            'routePrefix' => 'admin.events.session-types',
            'title' => __('Session Types'),
            'newLabel' => __('New Session Type'),
            'showBreakToggle' => true,
        ];
    }

    private function assertBelongsToEvent(Event $event, SessionType $sessionType): void
    {
        if ($sessionType->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
```

`app/Http/Controllers/Admin/LocationController.php` — the same shape with `Location`, `$event->locations()`, `'routePrefix' => 'admin.events.locations'`, `'title' => __('Locations')`, `'newLabel' => __('New Location')`, `'showBreakToggle' => false`, no `is_break` in store/update, and usage counted over both relations:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleListRequest;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocationController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.schedule-lists.index', $this->screen($event) + [
            'items' => $event->locations()->withCount(['agendaItems', 'workshops'])->get()
                ->each(fn (Location $location) => $location->setAttribute('usage_count', $location->agenda_items_count + $location->workshops_count)),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => new Location]);
    }

    public function store(ScheduleListRequest $request, Event $event): RedirectResponse
    {
        $event->locations()->create($request->safe()->only(['name_ar', 'name_en']) + [
            'sort_order' => (int) $event->locations()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, Location $location): View
    {
        $this->assertBelongsToEvent($event, $location);

        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => $location]);
    }

    public function update(ScheduleListRequest $request, Event $event, Location $location): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $location);
        $location->update($request->safe()->only(['name_ar', 'name_en']));

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, Location $location): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $location);
        $inUse = $location->agendaItems()->count() + $location->workshops()->count();

        if ($inUse > 0) {
            return redirect()->route('admin.events.locations.index', $event)
                ->with('error', trans_choice('In use by :count session — move it first.|In use by :count sessions or workshops — move them first.', $inUse, ['count' => $inUse]));
        }

        $location->delete();

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('locations', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Location::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** @return array<string, mixed> */
    private function screen(Event $event): array
    {
        return [
            'event' => $event,
            'routePrefix' => 'admin.events.locations',
            'title' => __('Locations'),
            'newLabel' => __('New Location'),
            'showBreakToggle' => false,
        ];
    }

    private function assertBelongsToEvent(Event $event, Location $location): void
    {
        if ($location->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
```

`resources/views/admin/schedule-lists/index.blade.php` (copy the flash-message and drag-handle markup conventions from `resources/views/admin/audience-tabs/index.blade.php`):

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$title.' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        <x-admin.button href="{{ route($routePrefix.'.create', $event) }}">{{ $newLabel }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-[#b42318]" role="alert">{{ session('error') }}</div>
    @endif

    @if($items->isEmpty())
        <x-admin.empty-state :message="__('Nothing here yet.')" />
    @else
        <p class="mb-4 text-sm text-hub-dark/60">{{ __('Drag to change the order they appear in.') }}</p>
        <x-admin.table>
            <thead>
                <tr><th class="w-8"></th><th>{{ __('Name (Arabic)') }}</th><th>{{ __('Name (English)') }}</th><th>{{ __('Used by') }}</th><th></th></tr>
            </thead>
            <tbody data-sortable data-sortable-url="{{ route($routePrefix.'.reorder', $event) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
                @foreach($items as $item)
                    <tr data-sortable-item="{{ $item->id }}">
                        <td><span class="adm-drag-handle cursor-grab text-hub-dark/40" aria-hidden="true">⋮⋮</span></td>
                        <td>{{ $item->name_ar }}</td>
                        <td>
                            {{ $item->name_en }}
                            @if($showBreakToggle && $item->is_break)
                                <span class="ms-2 rounded-full bg-hub-lavender px-2 py-0.5 text-xs font-bold text-hub-purple">{{ __('Break') }}</span>
                            @endif
                        </td>
                        <td>{{ $item->usage_count }}</td>
                        <td class="text-end">
                            <a href="{{ route($routePrefix.'.edit', [$event, $item]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route($routePrefix.'.destroy', [$event, $item]) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
```

(Before writing, open `resources/views/admin/audience-tabs/index.blade.php` and match its exact drag-handle element/class if it differs from `adm-drag-handle` above.)

`resources/views/admin/schedule-lists/form.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$item->exists ? __('Edit').' — '.$title : $newLabel" />

    <form method="POST" action="{{ $item->exists ? route($routePrefix.'.update', [$event, $item]) : route($routePrefix.'.store', $event) }}" class="adm-card p-6 max-w-xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $item->name_ar)" :value-en="old('name_en', $item->name_en)" />

        @if($showBreakToggle)
            <x-admin.field type="checkbox" name="is_break" label="{{ __('Show as a break (a slim line on the agenda)') }}" :checked="old('is_break', $item->is_break)" />
        @endif

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
```

- [ ] **Step 6: Routes, permission, sidebar**

`routes/web.php` — import both controllers and add inside the admin group, next to the agenda-items resource (reorder routes **before** the resources):

```php
        Route::post('events/{event}/session-types/reorder', [SessionTypeController::class, 'reorder'])->name('events.session-types.reorder');
        Route::resource('events.session-types', SessionTypeController::class)->except('show')->parameters(['session-types' => 'sessionType']);
        Route::post('events/{event}/locations/reorder', [LocationController::class, 'reorder'])->name('events.locations.reorder');
        Route::resource('events.locations', LocationController::class)->except('show');
```

`app/Enums/Permission.php` — `AgendaWorkshops` routes become:

```php
            self::AgendaWorkshops => ['admin.events.agenda-items.*', 'admin.events.workshops.*', 'admin.events.session-types.*', 'admin.events.locations.*'],
```

`resources/views/admin/partials/sidebar.blade.php` — add after the Agenda row in `$eventSections`:

```php
        ['prefix' => 'admin.events.session-types', 'permission' => \App\Enums\Permission::AgendaWorkshops, 'route' => 'admin.events.session-types.index', 'label' => __('Session Types')],
        ['prefix' => 'admin.events.locations', 'permission' => \App\Enums\Permission::AgendaWorkshops, 'route' => 'admin.events.locations.index', 'label' => __('Locations')],
```

- [ ] **Step 7: Translations**

Add (en = key; ar below), skipping keys that exist: Session Types → أنواع الجلسات; New Session Type → نوع جلسة جديد; Locations → الأماكن; New Location → مكان جديد; Name (Arabic) → الاسم (بالعربية); Name (English) → الاسم (بالإنجليزية); Used by → مستخدم في; Drag to change the order they appear in. → اسحب لتغيير ترتيب ظهورها.; Nothing here yet. → لا يوجد شيء هنا بعد.; Show as a break (a slim line on the agenda) → عرضها كاستراحة (سطر مختصر في الجدول); Saved. → تم الحفظ.; Deleted. → تم الحذف.; Break → استراحة; `In use by :count session — move it first.|In use by :count sessions or workshops — move them first.` → `مستخدم في جلسة واحدة — انقلها أولًا.|مستخدم في :count جلسات أو ورش — انقلها أولًا.`

- [ ] **Step 8: Run tests, full suite, commit**

Run: `php artisan test --compact tests/Feature/Admin/ScheduleListsTest.php tests/Unit/AdminPermissionsTest.php tests/Feature/Admin/PermissionAccessTest.php`
Expected: PASS. Then `php artisan test --compact` — all green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app database resources routes lang tests
git commit -m "feat: add per-event session types and locations for the agenda"
```

---

### Task 2: Restructure sessions and workshops (data)

**Files:**
- Create: migration `restructure_agenda_items_and_workshops`, `app/Models/Concerns/HasOrderedSpeakers.php`
- Modify: `app/Models/AgendaItem.php`, `app/Models/Workshop.php`, `app/Models/Speaker.php` (inverse relations), `database/factories/AgendaItemFactory.php`, `database/factories/WorkshopFactory.php`, `database/seeders/CcsEventSeeder.php`
- Delete: `app/Enums/AgendaItemType.php` (after Tasks 3–5 stop using it — see Step 8)
- Test: `tests/Feature/ScheduleRestructureTest.php` (new); rewrite `tests/Unit/Models/AgendaItemTest.php`, `tests/Unit/Models/WorkshopTest.php`

**Interfaces:**
- Consumes: `SessionType`, `Location`, `Event::sessionTypes()` (Task 1).
- Produces: `AgendaItem`: fillable `event_id, session_type_id, location_id, day_date, start_time, end_time, title_ar, title_en, description_ar, description_en, sort_order`; relations `sessionType()`, `location()`, `speakers()` (ordered by pivot `sort_order`); `title(): string`, `description(): ?string`. `Workshop`: adds fillable `day_date, start_time, end_time, location_id`; relations `location()`, `speakers()`; removes `speaker()`, `agendaItems()`; `isScheduled(): bool`. Trait method `syncSpeakersInOrder(array $speakerIds): void`. Factories: `AgendaItemFactory` default type = the event's "Session" type, state `ofType(string $nameEn)`; `WorkshopFactory` scheduled by default, state `unscheduled()`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ScheduleRestructureTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleRestructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_and_workshops_keep_several_speakers_in_order(): void
    {
        $event = Event::factory()->create();
        [$a, $b, $c] = Speaker::factory()->for($event)->count(3)->create()->all();
        $session = AgendaItem::factory()->for($event)->create();
        $workshop = Workshop::factory()->for($event)->create();

        $session->syncSpeakersInOrder([$c->id, $a->id, $b->id]);
        $workshop->syncSpeakersInOrder([$b->id, $a->id]);

        $this->assertSame([$c->id, $a->id, $b->id], $session->fresh()->speakers->pluck('id')->all());
        $this->assertSame([$b->id, $a->id], $workshop->fresh()->speakers->pluck('id')->all());

        $session->syncSpeakersInOrder([$a->id]);
        $this->assertSame([$a->id], $session->fresh()->speakers->pluck('id')->all());
    }

    public function test_deleting_a_speaker_removes_them_from_sessions_and_workshops(): void
    {
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();
        $session = AgendaItem::factory()->for($event)->create();
        $workshop = Workshop::factory()->for($event)->create();
        $session->syncSpeakersInOrder([$speaker->id]);
        $workshop->syncSpeakersInOrder([$speaker->id]);

        $speaker->delete();

        $this->assertCount(0, $session->fresh()->speakers);
        $this->assertCount(0, $workshop->fresh()->speakers);
    }

    public function test_a_session_has_a_type_a_location_and_a_rich_description(): void
    {
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create(['name_en' => 'Main Stage']);
        $session = AgendaItem::factory()->for($event)->ofType('Panel')->create([
            'location_id' => $location->id,
            'description_en' => '<p>Hello</p><script>alert(1)</script>',
        ]);

        $this->assertSame('Panel', $session->sessionType->name_en);
        $this->assertSame('Main Stage', $session->location->name_en);
        $this->assertStringNotContainsString('<script>', $session->fresh()->description_en);
    }

    public function test_a_location_used_only_by_a_workshop_cannot_be_deleted(): void
    {
        $admin = \App\Models\User::factory()->create();
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create();
        Workshop::factory()->for($event)->create(['location_id' => $location->id]);

        $this->actingAs($admin)->delete(route('admin.events.locations.destroy', [$event, $location]))
            ->assertSessionHas('error');

        $this->assertModelExists($location);
    }

    public function test_a_session_type_in_use_cannot_be_deleted(): void
    {
        $admin = \App\Models\User::factory()->create();
        $event = Event::factory()->create();
        $session = AgendaItem::factory()->for($event)->ofType('Keynote')->create();

        $this->actingAs($admin)->delete(route('admin.events.session-types.destroy', [$event, $session->sessionType]))
            ->assertSessionHas('error');

        $this->assertModelExists($session->sessionType);
    }

    /**
     * Rolls just the restructure migration back, recreates the old shape of data, and migrates
     * again. Revisit if a later migration comes to depend on the new columns.
     */
    public function test_existing_agenda_and_workshops_move_to_the_new_structure(): void
    {
        $migration = collect(glob(database_path('migrations/*_restructure_agenda_items_and_workshops.php')))->sole();
        $this->artisan('migrate:rollback', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();
        $workshopId = DB::table('workshops')->insertGetId([
            'event_id' => $event->id, 'speaker_id' => $speaker->id, 'slug' => 'w-1', 'name_ar' => 'و', 'name_en' => 'W',
            'capacity' => 10, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $row = fn (array $extra) => $extra + [
            'event_id' => $event->id, 'day_date' => '2026-08-15', 'title_ar' => 'ع', 'title_en' => 'T',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('agenda_items')->insert($row(['type' => 'panel', 'speaker_id' => $speaker->id, 'start_time' => '09:00', 'end_time' => '10:00', 'title_en' => 'Kept panel']));
        DB::table('agenda_items')->insert($row(['type' => 'mystery', 'start_time' => '10:00', 'end_time' => '11:00', 'title_en' => 'Odd type']));
        DB::table('agenda_items')->insert($row(['type' => 'workshop', 'workshop_id' => $workshopId, 'start_time' => '14:00', 'end_time' => '15:30', 'title_en' => 'Workshop slot']));

        $this->artisan('migrate', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $panel = AgendaItem::where('title_en', 'Kept panel')->sole();
        $this->assertSame('Panel', $panel->sessionType->name_en);
        $this->assertSame([$speaker->id], $panel->speakers->pluck('id')->all());
        $this->assertSame('Session', AgendaItem::where('title_en', 'Odd type')->sole()->sessionType->name_en);
        $this->assertDatabaseMissing('agenda_items', ['title_en' => 'Workshop slot']);

        $workshop = Workshop::findOrFail($workshopId);
        $this->assertSame('2026-08-15', $workshop->day_date->toDateString());
        $this->assertSame('14:00', $workshop->start_time->format('H:i'));
        $this->assertSame('15:30', $workshop->end_time->format('H:i'));
        $this->assertSame([$speaker->id], $workshop->speakers->pluck('id')->all());
    }
}
```

Replace `tests/Unit/Models/AgendaItemTest.php` body:

```php
class AgendaItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_item_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $this->assertTrue($item->event->is($event));
    }

    public function test_agenda_item_defaults_to_its_events_session_type(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $this->assertSame('Session', $item->sessionType->name_en);
        $this->assertSame($event->id, $item->sessionType->event_id);
    }
}
```

(imports: `App\Models\AgendaItem`, `App\Models\Event`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Tests\TestCase` — drop `AgendaItemType`, `Workshop`.)

Replace `tests/Unit/Models/WorkshopTest.php` body:

```php
class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $this->assertTrue($workshop->event->is($event));
    }

    public function test_workshop_can_have_several_speakers(): void
    {
        $event = Event::factory()->create();
        $speakers = Speaker::factory()->for($event)->count(2)->create();
        $workshop = Workshop::factory()->for($event)->create();

        $workshop->syncSpeakersInOrder($speakers->pluck('id')->all());

        $this->assertCount(2, $workshop->fresh()->speakers);
    }

    public function test_workshop_uses_slug_as_route_key(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'ai-content-workshop']);

        $this->assertSame('slug', $workshop->getRouteKeyName());
    }

    public function test_a_workshop_knows_whether_it_is_scheduled(): void
    {
        $this->assertTrue(Workshop::factory()->create()->isScheduled());
        $this->assertFalse(Workshop::factory()->unscheduled()->create()->isScheduled());
    }
}
```

(imports: `Event`, `Speaker`, `Workshop`, `RefreshDatabase`, `TestCase`.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Feature/ScheduleRestructureTest.php tests/Unit/Models/AgendaItemTest.php tests/Unit/Models/WorkshopTest.php`
Expected: FAIL — `Call to undefined method … syncSpeakersInOrder()` / missing migration file.

- [ ] **Step 3: The restructure migration**

`php artisan make:migration restructure_agenda_items_and_workshops --no-interaction`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions get a session type, a location, a description and several ordered speakers;
 * workshops get their own schedule, location and speakers and stop needing an agenda item to
 * appear on the schedule. Old values are read as plain strings so the enum can be deleted.
 */
return new class extends Migration
{
    /** @var array<string, string> old agenda_items.type => default type's English name */
    private const TYPE_NAMES = [
        'keynote' => 'Keynote', 'session' => 'Session', 'workshop' => 'Workshop', 'break' => 'Break', 'panel' => 'Panel',
    ];

    public function up(): void
    {
        Schema::create('agenda_item_speaker', function (Blueprint $table) {
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['agenda_item_id', 'speaker_id']);
        });

        Schema::create('speaker_workshop', function (Blueprint $table) {
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['workshop_id', 'speaker_id']);
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreignId('session_type_id')->nullable()->after('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->after('session_type_id')->constrained()->nullOnDelete();
            $table->longText('description_ar')->nullable()->after('title_en');
            $table->longText('description_en')->nullable()->after('description_ar');
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->date('day_date')->nullable()->after('name_en');
            $table->time('start_time')->nullable()->after('day_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->foreignId('location_id')->nullable()->after('end_time')->constrained()->nullOnDelete();
            $table->longText('description_ar')->nullable()->change();
            $table->longText('description_en')->nullable()->change();
        });

        foreach (DB::table('agenda_items')->get() as $item) {
            $typeName = self::TYPE_NAMES[$item->type] ?? 'Session';
            $typeId = DB::table('session_types')->where('event_id', $item->event_id)->where('name_en', $typeName)->value('id')
                ?? DB::table('session_types')->where('event_id', $item->event_id)->orderBy('sort_order')->value('id');

            DB::table('agenda_items')->where('id', $item->id)->update(['session_type_id' => $typeId]);

            if ($item->speaker_id !== null) {
                DB::table('agenda_item_speaker')->insert(['agenda_item_id' => $item->id, 'speaker_id' => $item->speaker_id, 'sort_order' => 0]);
            }
        }

        foreach (DB::table('workshops')->whereNotNull('speaker_id')->get(['id', 'speaker_id']) as $workshop) {
            DB::table('speaker_workshop')->insert(['workshop_id' => $workshop->id, 'speaker_id' => $workshop->speaker_id, 'sort_order' => 0]);
        }

        // A workshop takes over the schedule of the (first) agenda item that pointed at it; that
        // item then duplicates the workshop on the merged schedule, so it goes.
        $linked = DB::table('agenda_items')->whereNotNull('workshop_id')->orderBy('day_date')->orderBy('start_time')->get();
        foreach ($linked->groupBy('workshop_id') as $workshopId => $items) {
            $first = $items->first();
            DB::table('workshops')->where('id', $workshopId)->update([
                'day_date' => $first->day_date, 'start_time' => $first->start_time, 'end_time' => $first->end_time,
            ]);
        }
        DB::table('agenda_items')->whereIn('id', $linked->pluck('id'))->delete();

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
            $table->dropConstrainedForeignId('workshop_id');
            $table->dropColumn('type');
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->unsignedBigInteger('session_type_id')->nullable(false)->change();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->foreignId('speaker_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreignId('speaker_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->foreignId('workshop_id')->nullable()->after('speaker_id')->constrained()->nullOnDelete();
            $table->string('type')->default('session')->after('title_en');
        });

        foreach (DB::table('agenda_items')->get(['id', 'session_type_id']) as $item) {
            $name = DB::table('session_types')->where('id', $item->session_type_id)->value('name_en');
            $type = array_search($name, self::TYPE_NAMES, true) ?: 'session';
            $speakerId = DB::table('agenda_item_speaker')->where('agenda_item_id', $item->id)->orderBy('sort_order')->value('speaker_id');
            DB::table('agenda_items')->where('id', $item->id)->update(['type' => $type, 'speaker_id' => $speakerId]);
        }

        foreach (DB::table('workshops')->pluck('id') as $workshopId) {
            $speakerId = DB::table('speaker_workshop')->where('workshop_id', $workshopId)->orderBy('sort_order')->value('speaker_id');
            DB::table('workshops')->where('id', $workshopId)->update(['speaker_id' => $speakerId]);
        }

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_type_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['description_ar', 'description_en']);
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['day_date', 'start_time', 'end_time']);
            $table->text('description_ar')->nullable()->change();
            $table->text('description_en')->nullable()->change();
        });

        Schema::dropIfExists('speaker_workshop');
        Schema::dropIfExists('agenda_item_speaker');
    }
};
```

- [ ] **Step 4: Models**

`app/Models/Concerns/HasOrderedSpeakers.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Sessions and workshops keep their speakers in an order the admin chooses (pivot sort_order);
 * this writes that order in one go.
 */
trait HasOrderedSpeakers
{
    /**
     * @param  list<int|string>  $speakerIds  in display order
     */
    public function syncSpeakersInOrder(array $speakerIds): void
    {
        $this->speakers()->sync(
            collect(array_values(array_unique(array_map('intval', $speakerIds))))
                ->mapWithKeys(fn (int $id, int $position) => [$id => ['sort_order' => $position]])
                ->all()
        );
    }
}
```

`app/Models/AgendaItem.php` — full replacement:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Models\Concerns\HasOrderedSpeakers;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgendaItem extends Model
{
    use HasFactory, HasOrderedSpeakers;

    protected $fillable = [
        'event_id', 'session_type_id', 'location_id', 'day_date', 'start_time', 'end_time',
        'title_ar', 'title_en', 'description_ar', 'description_en', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'description_ar' => SanitizedRichText::class,
            'description_en' => SanitizedRichText::class,
        ];
    }

    public function title(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function description(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sessionType(): BelongsTo
    {
        return $this->belongsTo(SessionType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    protected static function newFactory(): AgendaItemFactory
    {
        return AgendaItemFactory::new();
    }
}
```

`app/Models/Workshop.php` — add `use App\Models\Concerns\HasOrderedSpeakers;` and `BelongsToMany` import, add the trait to the `use` line, add to `$fillable`: `'day_date', 'start_time', 'end_time', 'location_id'` (remove `'speaker_id'`), add to `casts()`: `'day_date' => 'date', 'start_time' => 'datetime', 'end_time' => 'datetime'`; delete `speaker()` and `agendaItems()`; add:

```php
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * Has a day and times, so it can appear on the agenda.
     */
    public function isScheduled(): bool
    {
        return $this->day_date !== null && $this->start_time !== null && $this->end_time !== null;
    }
```

`app/Models/Speaker.php` — add inverse relations (used by nothing yet but keep the pivots symmetrical):

```php
    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(AgendaItem::class);
    }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class);
    }
```

- [ ] **Step 5: Factories**

`database/factories/AgendaItemFactory.php` `definition()`:

```php
        return [
            'event_id' => Event::factory(),
            'session_type_id' => fn (array $attributes) => SessionType::where('event_id', $attributes['event_id'])->where('name_en', 'Session')->value('id')
                ?? SessionType::factory()->create(['event_id' => $attributes['event_id']])->id,
            'location_id' => null,
            'day_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title_ar' => $this->faker->sentence(3),
            'title_en' => $this->faker->sentence(3),
            'description_ar' => null,
            'description_en' => null,
            'sort_order' => 0,
        ];
```

plus:

```php
    /**
     * One of the event's own types, by its English name ("Keynote", "Break", …).
     */
    public function ofType(string $nameEn): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type_id' => fn (array $resolved) => SessionType::where('event_id', $resolved['event_id'])->where('name_en', $nameEn)->value('id'),
        ]);
    }
```

(replace the `AgendaItemType` import with `App\Models\SessionType`).

`database/factories/WorkshopFactory.php` `definition()`: remove `'speaker_id' => null,`; add `'day_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'), 'start_time' => '11:00', 'end_time' => '12:30', 'location_id' => null,`; add:

```php
    public function unscheduled(): static
    {
        return $this->state(fn (array $attributes) => ['day_date' => null, 'start_time' => null, 'end_time' => null]);
    }
```

- [ ] **Step 6: Seeder**

In `database/seeders/CcsEventSeeder.php`: remove `'speaker_id' => $speakerKareem->id,` from the first `Workshop::create`, add `'day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:30',` to it and `'day_date' => '2026-08-16', 'start_time' => '12:00', 'end_time' => '13:30',` to the second (capture it as `$editingWorkshop`); before the workshops, create two locations:

```php
        $stage = $event->locations()->create(['name_ar' => 'المسرح الرئيسي', 'name_en' => 'Main Stage', 'sort_order' => 0]);
        $roomA = $event->locations()->create(['name_ar' => 'القاعة A', 'name_en' => 'Room A', 'sort_order' => 1]);
```

set `'location_id' => $roomA->id` on both workshops, then `$workshop->syncSpeakersInOrder([$speakerKareem->id]);`. Replace the old `AgendaItem::create([...workshop...])` block with a real opening session:

```php
        $opening = AgendaItem::create([
            'event_id' => $event->id,
            'session_type_id' => $event->sessionTypes()->where('name_en', 'Keynote')->value('id'),
            'location_id' => $stage->id,
            'day_date' => '2026-08-15',
            'start_time' => '09:00',
            'end_time' => '09:45',
            'title_ar' => 'الكلمة الافتتاحية',
            'title_en' => 'Opening Keynote',
            'description_ar' => '<p>افتتاح القمة ورؤية هذا العام لصناعة المحتوى.</p>',
            'description_en' => '<p>Opening the summit and this year\'s outlook for content creation.</p>',
            'sort_order' => 0,
        ]);
        $opening->syncSpeakersInOrder([$speakerKareem->id]);
```

Remove the `AgendaItemType` import. (If the seeder creates the event with `Event::create`, the default types exist already via the `created` hook; if it uses `updateOrCreate`/`firstOrCreate` on a pre-existing event, call `SessionType::seedDefaultsFor($event)` only when `$event->sessionTypes()->doesntExist()`.)

- [ ] **Step 7: Run tests**

Run: `php artisan test --compact tests/Feature/ScheduleRestructureTest.php tests/Unit/Models/AgendaItemTest.php tests/Unit/Models/WorkshopTest.php tests/Feature/CcsEventSeederTest.php tests/Feature/DatabaseSeederTest.php`
Expected: PASS.

- [ ] **Step 8: Move the remaining callers onto the new structure**

The old admin forms, public views and tests still use `speaker_id`, `workshop_id`, `type`, `->speaker`, `->agendaItems` and `AgendaItemType`. Tasks 3–5 give them their final form; this step makes the minimum changes so the whole suite passes at the end of this task:

- `AgendaController::show`: `->with(['speaker', 'workshop'])` → `->with(['speakers', 'sessionType', 'location'])`.
- `resources/views/agenda/show.blade.php`: `{{ $item->type->label() }}` → `{{ $item->sessionType->name() }}`; replace the `@if($item->speaker) … @endif` block with `@if($item->speakers->isNotEmpty())<div class="text-sm text-gray-500">{{ $item->speakers->map(fn ($s) => app()->getLocale() === 'ar' ? $s->name_ar : $s->name_en)->implode('، ') }}</div>@endif`.
- Public `WorkshopController`: `with('speaker')` → `with('speakers')`; `load(['speaker', 'agendaItems'])` → `load(['speakers', 'location'])`.
- `resources/views/workshops/show.blade.php`: replace the `@if($workshop->agendaItems->isNotEmpty()) … @endif` block with one chip shown when `$workshop->isScheduled()` (`{{ $workshop->day_date->format('M j') }} · {{ $workshop->start_time->format('H:i') }}–{{ $workshop->end_time->format('H:i') }}`), and the speaker block with the joined names of `$workshop->speakers`.
- `AgendaItemRequest`: drop the `speaker_id`, `workshop_id` and `type` rules; add `'session_type_id' => ['required', Rule::exists('session_types', 'id')->where('event_id', $this->route('event')->id)]`. `WorkshopRequest`: drop `speaker_id`. The admin forms: the type select becomes `session_type_id` over `$event->sessionTypes` (option value = id, label = `name()`); remove the speaker and workshop selects. `AgendaItemController` create/edit pass `'types' => $event->sessionTypes` instead of `AgendaItemType::cases()` and drop `workshops`.
- Tests: in `AgendaItemCrudTest`, `'type' => 'keynote'` → `'session_type_id' => $event->sessionTypes()->where('name_en', 'Keynote')->value('id')` and the `assertDatabaseHas` `type` key → `session_type_id`; delete `test_creating_an_agenda_item_rejects_a_speaker_from_a_different_event` and `…_a_workshop_from_a_different_event` (Task 3 rewrites this file with the speaker-ids versions). In `WorkshopCrudTest` drop `speaker_id` from the payloads and assertion and delete `test_creating_a_workshop_rejects_a_speaker_from_a_different_event` (Task 3 re-adds it). In `AgendaPageTest` replace `'speaker_id' => $speaker->id` with a `->syncSpeakersInOrder([$speaker->id])` call after `create()`. In `WorkshopPagesTest` do the same for the show test and delete `test_show_displays_scheduled_session_time_when_linked_to_an_agenda_item` (Task 5 adds the replacement).
- `HubTranslationCoverageTest`: remove `AgendaItemType::class` from the enum list and its import, delete `test_every_agenda_item_type_has_an_arabic_translation`, then delete `app/Enums/AgendaItemType.php`. `grep -rn AgendaItemType app database resources tests` must return nothing.

Run: `php artisan test --compact` — expected all green.

- [ ] **Step 9: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app database resources tests lang
git commit -m "feat: give sessions and workshops several speakers, a type, a location and their own schedule"
```

---

### Task 3: Admin forms — speaker picker, sessions, workshops

**Files:**
- Create: `resources/views/components/admin/speaker-picker.blade.php`
- Modify: `app/Http/Requests/Admin/AgendaItemRequest.php`, `app/Http/Requests/Admin/WorkshopRequest.php`, `app/Http/Controllers/Admin/AgendaItemController.php`, `app/Http/Controllers/Admin/WorkshopController.php`, `resources/views/admin/agenda-items/form.blade.php`, `resources/views/admin/agenda-items/index.blade.php`, `resources/views/admin/workshops/form.blade.php`, `lang/*.json`
- Test: rewrite `tests/Feature/Admin/AgendaItemCrudTest.php`, `tests/Feature/Admin/WorkshopCrudTest.php`

**Interfaces:**
- Consumes: `syncSpeakersInOrder()`, `Event::sessionTypes()`, `Event::locations()`, `Workshop::isScheduled()`.
- Produces: `<x-admin.speaker-picker :speakers="$event->speakers" :selected="[ids…]" />` posting `speaker_ids[]` in order.

- [ ] **Step 1: Write the failing tests**

Replace `tests/Feature/Admin/AgendaItemCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Location;
use App\Models\SessionType;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create(['start_date' => '2026-08-15', 'end_date' => '2026-08-16']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'day_date' => '2026-08-15', 'start_time' => '09:00', 'end_time' => '10:00',
            'title_ar' => 'الافتتاح', 'title_en' => 'Opening',
            'session_type_id' => $this->event->sessionTypes()->where('name_en', 'Keynote')->value('id'),
        ];
    }

    public function test_admin_creates_a_session_with_several_speakers_in_order(): void
    {
        $location = Location::factory()->for($this->event)->create();
        [$a, $b] = Speaker::factory()->for($this->event)->count(2)->create()->all();

        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'location_id' => $location->id,
            'description_en' => '<p>Long <strong>details</strong></p>',
            'speaker_ids' => [$b->id, $a->id],
        ]))->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $item = AgendaItem::where('title_en', 'Opening')->sole();
        $this->assertSame('Keynote', $item->sessionType->name_en);
        $this->assertTrue($item->location->is($location));
        $this->assertSame([$b->id, $a->id], $item->speakers->pluck('id')->all());
        $this->assertStringContainsString('<strong>details</strong>', $item->description_en);
    }

    public function test_editing_can_reorder_and_remove_speakers(): void
    {
        [$a, $b, $c] = Speaker::factory()->for($this->event)->count(3)->create()->all();
        $item = AgendaItem::factory()->for($this->event)->create(['day_date' => '2026-08-15']);
        $item->syncSpeakersInOrder([$a->id, $b->id, $c->id]);

        $this->actingAs($this->admin)->put(route('admin.events.agenda-items.update', [$this->event, $item]), $this->payload([
            'speaker_ids' => [$c->id, $a->id],
        ]))->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $this->assertSame([$c->id, $a->id], $item->fresh()->speakers->pluck('id')->all());

        $this->actingAs($this->admin)->put(route('admin.events.agenda-items.update', [$this->event, $item]), $this->payload());
        $this->assertCount(0, $item->fresh()->speakers);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload(['start_time' => '10:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors('end_time');
    }

    public function test_the_day_must_fall_within_the_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload(['day_date' => '2026-08-20']))
            ->assertSessionHasErrors('day_date');
    }

    public function test_type_location_and_speakers_must_belong_to_this_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'session_type_id' => SessionType::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'speaker_ids' => [Speaker::factory()->create()->id],
        ]))->assertSessionHasErrors(['session_type_id', 'location_id', 'speaker_ids.0']);

        $this->assertDatabaseCount('agenda_items', 0);
    }

    public function test_a_speaker_cannot_be_listed_twice(): void
    {
        $speaker = Speaker::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'speaker_ids' => [$speaker->id, $speaker->id],
        ]))->assertSessionHasErrors('speaker_ids.0');
    }

    public function test_admin_can_delete_an_agenda_item(): void
    {
        $item = AgendaItem::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->delete(route('admin.events.agenda-items.destroy', [$this->event, $item]))
            ->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $this->assertModelMissing($item);
    }

    public function test_the_list_shows_time_range_type_location_and_speaker_count(): void
    {
        $location = Location::factory()->for($this->event)->create(['name_en' => 'Main Stage']);
        $item = AgendaItem::factory()->for($this->event)->ofType('Panel')->create([
            'start_time' => '11:15', 'end_time' => '12:00', 'location_id' => $location->id,
        ]);
        $item->syncSpeakersInOrder(Speaker::factory()->for($this->event)->count(3)->create()->pluck('id')->all());

        $this->actingAs($this->admin)->get(route('admin.events.agenda-items.index', ['event' => $this->event, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('11:15–12:00')
            ->assertSee('Panel')
            ->assertSee('Main Stage')
            ->assertSee(route('admin.events.workshops.index', $this->event), false);
    }

    public function test_the_form_shows_the_speaker_picker_and_editor(): void
    {
        $item = AgendaItem::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->get(route('admin.events.agenda-items.edit', [$this->event, $item]))
            ->assertOk()
            ->assertSee('data-speaker-picker', false)
            ->assertSee('name="description_en"', false)
            ->assertSee('name="session_type_id"', false)
            ->assertSee('name="location_id"', false);
    }
}
```

Replace `tests/Feature/Admin/WorkshopCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create(['start_date' => '2026-08-15', 'end_date' => '2026-08-16']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'slug' => 'ai-workshop', 'name_ar' => 'ورشة', 'name_en' => 'AI Workshop', 'capacity' => 30,
            'day_date' => '2026-08-15', 'start_time' => '14:00', 'end_time' => '15:30',
        ];
    }

    public function test_admin_creates_a_scheduled_workshop_with_speakers_and_location(): void
    {
        $location = Location::factory()->for($this->event)->create();
        [$a, $b] = Speaker::factory()->for($this->event)->count(2)->create()->all();

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'location_id' => $location->id, 'speaker_ids' => [$b->id, $a->id],
        ]))->assertRedirect(route('admin.events.workshops.index', $this->event));

        $workshop = Workshop::where('slug', 'ai-workshop')->sole();
        $this->assertSame('14:00', $workshop->start_time->format('H:i'));
        $this->assertTrue($workshop->location->is($location));
        $this->assertSame([$b->id, $a->id], $workshop->speakers->pluck('id')->all());
    }

    public function test_a_workshop_needs_a_day_and_times_within_the_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'day_date' => '', 'start_time' => '', 'end_time' => '',
        ]))->assertSessionHasErrors(['day_date', 'start_time', 'end_time']);

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'day_date' => '2026-09-01', 'start_time' => '15:00', 'end_time' => '14:00',
        ]))->assertSessionHasErrors(['day_date', 'end_time']);
    }

    public function test_creating_a_workshop_requires_a_unique_slug(): void
    {
        Workshop::factory()->create(['slug' => 'ai-workshop']);

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_speakers_and_location_must_belong_to_this_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'location_id' => Location::factory()->create()->id,
            'speaker_ids' => [Speaker::factory()->create()->id],
        ]))->assertSessionHasErrors(['location_id', 'speaker_ids.0']);
    }

    public function test_admin_can_delete_a_workshop(): void
    {
        $workshop = Workshop::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->delete(route('admin.events.workshops.destroy', [$this->event, $workshop]))
            ->assertRedirect(route('admin.events.workshops.index', $this->event));

        $this->assertModelMissing($workshop);
    }

    public function test_the_index_and_edit_pages_load(): void
    {
        $workshop = Workshop::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->get(route('admin.events.workshops.index', $this->event))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.events.workshops.edit', [$this->event, $workshop]))
            ->assertOk()
            ->assertSee('data-speaker-picker', false)
            ->assertSee('name="day_date"', false);
    }
}
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Feature/Admin/AgendaItemCrudTest.php tests/Feature/Admin/WorkshopCrudTest.php`
Expected: FAIL — speakers not saved / picker markup missing / day range not validated.

- [ ] **Step 3: Requests**

`AgendaItemRequest::rules()`:

```php
    public function rules(): array
    {
        /** @var \App\Models\Event $event */
        $event = $this->route('event');

        return [
            'session_type_id' => ['required', 'integer', Rule::exists('session_types', 'id')->where('event_id', $event->id)],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('event_id', $event->id)],
            'day_date' => ['required', 'date', 'after_or_equal:'.$event->start_date->toDateString(), 'before_or_equal:'.$event->end_date->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'speaker_ids' => ['nullable', 'array'],
            'speaker_ids.*' => ['integer', 'distinct', Rule::exists('speakers', 'id')->where('event_id', $event->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'session_type_id' => __('Type'),
            'location_id' => __('Location'),
            'speaker_ids.*' => __('Speaker'),
        ];
    }
```

`WorkshopRequest::rules()`: remove `speaker_id`; add the same `location_id`, `day_date` (required, within event), `start_time` (required, `date_format:H:i`), `end_time` (required, after start), `speaker_ids`, `speaker_ids.*` rules and the `attributes()` above (without `session_type_id`).

- [ ] **Step 4: Controllers**

`AgendaItemController` — replace `use App\Enums\AgendaItemType;` usage; `index` eager-loads `['sessionType', 'location']` and `withCount('speakers')`; `create`/`edit` pass:

```php
        return view('admin.agenda-items.form', [
            'event' => $event, 'item' => $item,
            'speakers' => $event->speakers, 'types' => $event->sessionTypes, 'locations' => $event->locations,
        ]);
```

(with `$item = new AgendaItem` in create); `store`/`update` save then sync:

```php
    public function store(AgendaItemRequest $request, Event $event): RedirectResponse
    {
        $item = $event->agendaItems()->create($request->safe()->except('speaker_ids'));
        $item->syncSpeakersInOrder($request->validated('speaker_ids') ?? []);

        return redirect()->route('admin.events.agenda-items.index', $event)->with('success', __('Saved.'));
    }
```

```php
        $agendaItem->update($request->safe()->except('speaker_ids'));
        $agendaItem->syncSpeakersInOrder($request->validated('speaker_ids') ?? []);
```

`Admin\WorkshopController` — the same pattern: forms get `'speakers' => $event->speakers, 'locations' => $event->locations`; `store`/`update` use `->safe()->except('speaker_ids')` then `syncSpeakersInOrder(...)`.

- [ ] **Step 5: Speaker picker component**

`resources/views/components/admin/speaker-picker.blade.php`:

```blade
{{-- An ordered list of speakers for a session or workshop. Submits speaker_ids[] in the order
     shown; the first speaker is the one shown first on the public card. --}}
@props(['speakers', 'selected' => []])

@php
    $options = $speakers->map(fn ($speaker) => [
        'id' => $speaker->id,
        'name' => app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en,
        'photo' => $speaker->photoUrl(),
        'initials' => mb_strtoupper(mb_substr($speaker->name_en, 0, 1)),
    ])->values();
@endphp

<div class="mb-5" data-speaker-picker
    x-data="{
        options: @js($options),
        chosen: @js(array_values(array_map('intval', $selected))),
        adding: '',
        speaker(id) { return this.options.find((option) => option.id === id) },
        get available() { return this.options.filter((option) => ! this.chosen.includes(option.id)) },
        add() { if (this.adding !== '') { this.chosen.push(Number(this.adding)); this.adding = '' } },
        move(index, step) {
            const target = index + step;
            if (target < 0 || target >= this.chosen.length) { return }
            [this.chosen[index], this.chosen[target]] = [this.chosen[target], this.chosen[index]];
        },
        remove(index) { this.chosen.splice(index, 1) },
    }">
    <span class="adm-label">{{ __('Speakers') }}</span>

    <ol class="flex flex-col gap-2 mb-3" x-show="chosen.length > 0">
        <template x-for="(id, index) in chosen" :key="id">
            <li class="flex items-center gap-3 rounded-xl border border-hub-purple/15 bg-white px-3 py-2">
                <input type="hidden" name="speaker_ids[]" :value="id">
                <template x-if="speaker(id)?.photo"><img :src="speaker(id).photo" alt="" class="w-9 h-9 rounded-full object-cover"></template>
                <template x-if="! speaker(id)?.photo"><span class="w-9 h-9 rounded-full bg-hub-lavender text-hub-purple font-bold flex items-center justify-center" x-text="speaker(id)?.initials"></span></template>
                <span class="flex-1 font-semibold text-sm" x-text="speaker(id)?.name"></span>
                <button type="button" class="adm-btn adm-btn-secondary px-2.5 py-1" @click="move(index, -1)" :disabled="index === 0" aria-label="{{ __('Move up') }}">↑</button>
                <button type="button" class="adm-btn adm-btn-secondary px-2.5 py-1" @click="move(index, 1)" :disabled="index === chosen.length - 1" aria-label="{{ __('Move down') }}">↓</button>
                <button type="button" class="adm-btn-danger" @click="remove(index)">{{ __('Remove') }}</button>
            </li>
        </template>
    </ol>
    <p class="text-sm text-hub-dark/55 mb-3" x-show="chosen.length === 0">{{ __('No speakers yet.') }}</p>

    <div class="flex gap-2" x-show="available.length > 0">
        <select class="adm-input" x-model="adding" aria-label="{{ __('Add speaker') }}">
            <option value="">{{ __('Add speaker…') }}</option>
            <template x-for="option in available" :key="option.id">
                <option :value="option.id" x-text="option.name"></option>
            </template>
        </select>
        <button type="button" class="adm-btn adm-btn-primary" @click="add()">{{ __('Add') }}</button>
    </div>

    @error('speaker_ids.*') <p class="text-sm mt-1.5 text-[#b42318]">{{ $message }}</p> @enderror
</div>
```

(This picker deliberately uses a plain `<select>` bound with `x-model` instead of `data-nice-select`: its options change as speakers are added and removed. Reordering is by ↑/↓ buttons — keyboard-reachable — rather than drag.)

- [ ] **Step 6: Forms and list**

`resources/views/admin/agenda-items/form.blade.php` — replace the body of the `<form>` between `@if($item->exists) @method('PUT') @endif` and the submit button with:

```blade
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="date" name="day_date" label="{{ __('Day') }}" :value="old('day_date', optional($item->day_date)->toDateString())" required />
            <x-admin.field type="time" name="start_time" label="{{ __('Start Time') }}" :value="old('start_time', optional($item->start_time)->format('H:i'))" required />
            <x-admin.field type="time" name="end_time" label="{{ __('End Time') }}" :value="old('end_time', optional($item->end_time)->format('H:i'))" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="select" name="session_type_id" label="{{ __('Type') }}" required>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" @selected((string) old('session_type_id', $item->session_type_id) === (string) $type->id)>{{ $type->name() }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field type="select" name="location_id" label="{{ __('Location') }}">
                <option value="">{{ __('No location') }}</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) old('location_id', $item->location_id) === (string) $location->id)>{{ $location->name() }}</option>
                @endforeach
            </x-admin.field>
        </div>
        <p class="-mt-3 mb-5 text-xs text-hub-dark/55">
            <a class="text-hub-purple hover:underline" href="{{ route('admin.events.session-types.index', $event) }}">{{ __('Manage types') }}</a> ·
            <a class="text-hub-purple hover:underline" href="{{ route('admin.events.locations.index', $event) }}">{{ __('Manage locations') }}</a>
        </p>

        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $item->title_ar)" :value-en="old('title_en', $item->title_en)" />
        <x-admin.bilingual-field type="richtext" name="description" label="{{ __('Description') }}" :value-ar="old('description_ar', $item->description_ar)" :value-en="old('description_en', $item->description_en)" />

        <x-admin.speaker-picker :speakers="$speakers" :selected="old('speaker_ids', $item->exists ? $item->speakers->pluck('id')->all() : [])" />
```

`resources/views/admin/workshops/form.blade.php` — replace the `speaker_id` select with:

```blade
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="date" name="day_date" label="{{ __('Day') }}" :value="old('day_date', optional($workshop->day_date)->toDateString())" required />
            <x-admin.field type="time" name="start_time" label="{{ __('Start Time') }}" :value="old('start_time', optional($workshop->start_time)->format('H:i'))" required />
            <x-admin.field type="time" name="end_time" label="{{ __('End Time') }}" :value="old('end_time', optional($workshop->end_time)->format('H:i'))" required />
        </div>

        <x-admin.field type="select" name="location_id" label="{{ __('Location') }}">
            <option value="">{{ __('No location') }}</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((string) old('location_id', $workshop->location_id) === (string) $location->id)>{{ $location->name() }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.speaker-picker :speakers="$speakers" :selected="old('speaker_ids', $workshop->exists ? $workshop->speakers->pluck('id')->all() : [])" />
```

`resources/views/admin/agenda-items/index.blade.php` — table header becomes Day / Time / Title / Type / Location / Speakers / (actions); rows:

```blade
                        <td>{{ $item->day_date->toDateString() }}</td>
                        <td dir="ltr">{{ $item->start_time->format('H:i') }}–{{ $item->end_time->format('H:i') }}</td>
                        <td>{{ $item->title() }}</td>
                        <td>{{ $item->sessionType->name() }}</td>
                        <td>{{ $item->location?->name() ?? '—' }}</td>
                        <td>{{ $item->speakers_count }}</td>
```

and add under the page header: `<p class="mb-4 text-sm text-hub-dark/60">{{ __('Workshops appear on the agenda from their own schedule.') }} <a class="text-hub-purple hover:underline" href="{{ route('admin.events.workshops.index', $event) }}">{{ __('Manage workshops') }}</a></p>`. Change the delete button's `ml-2` to `ms-2`.

- [ ] **Step 7: Translations**

Add: Speakers (exists) ; Move up → تحريك لأعلى; Move down → تحريك لأسفل; Remove (exists?) → إزالة; No speakers yet. (exists); Add speaker → إضافة متحدث; Add speaker… → إضافة متحدث…; Add → إضافة; Location → المكان; No location → بدون مكان; Manage types → إدارة الأنواع; Manage locations → إدارة الأماكن; Workshops appear on the agenda from their own schedule. → تظهر ورش العمل في الجدول من مواعيدها الخاصة.; Manage workshops → إدارة ورش العمل; Type (exists); Speaker (exists).

- [ ] **Step 8: Run tests, full suite, commit**

Run: `php artisan test --compact tests/Feature/Admin/AgendaItemCrudTest.php tests/Feature/Admin/WorkshopCrudTest.php tests/Feature/HubTranslationCoverageTest.php` → PASS; then `php artisan test --compact` → all green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app resources lang tests
git commit -m "feat: pick several speakers, a type and a location on sessions and workshops"
```

---

### Task 4: The public schedule — cards, pop-up, agenda page

**Files:**
- Create: `app/Support/ScheduleEntry.php`, `app/Services/EventSchedule.php`, `resources/views/components/speaker-avatar.blade.php`, `resources/views/components/schedule-card.blade.php`, `resources/views/components/schedule-popup.blade.php`, `resources/js/schedule-popup.js`
- Modify: `app/Http/Controllers/AgendaController.php`, `resources/views/agenda/show.blade.php`, `resources/js/app.js`, `lang/*.json`
- Test: `tests/Unit/EventScheduleTest.php` (new, extends `Tests\TestCase`), rewrite `tests/Feature/AgendaPageTest.php`

**Interfaces:**
- Produces: `ScheduleEntry` — `kind` (`'session'|'workshop'`), `model` (AgendaItem|Workshop); methods `anchor(): string` (`session-<id>`/`workshop-<id>`), `title()`, `description(): ?string`, `typeLabel(): string`, `typeKey(): string` (`t<id>` or `workshop`), `isBreak(): bool`, `day(): CarbonInterface`, `start(): string`, `end(): string` (H:i), `locationName(): ?string`, `speakers(): Collection<Speaker>`, `workshop(): ?Workshop`.
- Produces: `EventSchedule::forEvent(Event): Collection<string, Collection<int, ScheduleEntry>>` keyed by `Y-m-d`, days ascending; within a day by start time, then sessions before workshops, then id.
- Produces: `<x-schedule-card :entry="$entry" :event="$event" />` (renders the card **and** its `<template id="detail-<anchor>">`), `<x-schedule-popup />` (one per page), Alpine `schedulePopup` listening for `schedule-open` window events with the anchor as detail.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/EventScheduleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Workshop;
use App\Services\EventSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_and_workshops_merge_per_day_in_time_order(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-16', 'start_time' => '09:00', 'title_en' => 'Day two']);
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '13:00', 'title_en' => 'Afternoon']);
        Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00', 'name_en' => 'Morning workshop']);

        app()->setLocale('en');
        $schedule = app(EventSchedule::class)->forEvent($event);

        $this->assertSame(['2026-08-15', '2026-08-16'], $schedule->keys()->all());
        $this->assertSame(['Morning workshop', 'Afternoon'], $schedule['2026-08-15']->map->title()->all());
        $this->assertSame(['workshop', 'session'], $schedule['2026-08-15']->map(fn ($entry) => $entry->kind)->all());
    }

    public function test_entries_at_the_same_time_list_sessions_before_workshops(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00']);
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00']);

        $kinds = app(EventSchedule::class)->forEvent($event)['2026-08-15']->map(fn ($entry) => $entry->kind)->all();

        $this->assertSame(['session', 'workshop'], $kinds);
    }

    public function test_unscheduled_workshops_stay_off_the_agenda(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->unscheduled()->create();

        $this->assertTrue(app(EventSchedule::class)->forEvent($event)->isEmpty());
    }
}
```

Replace `tests/Feature/AgendaPageTest.php` body:

```php
class AgendaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_agenda_shows_a_career_style_card_for_each_session(): void
    {
        $event = Event::factory()->create();
        $stage = Location::factory()->for($event)->create(['name_en' => 'Main Stage']);
        $withPhoto = Speaker::factory()->for($event)->create(['name_en' => 'Maya Chen', 'photo_path' => 'speakers/maya.jpg']);
        $noPhoto = Speaker::factory()->for($event)->create(['name_en' => 'Omar Ali', 'photo_path' => null]);
        $item = AgendaItem::factory()->for($event)->ofType('Panel')->create([
            'title_en' => 'Worth the Hype?', 'day_date' => '2026-08-15', 'start_time' => '11:15', 'end_time' => '12:00',
            'location_id' => $stage->id, 'description_en' => '<p>Are certificates a must?</p>',
        ]);
        $item->syncSpeakersInOrder([$withPhoto->id, $noPhoto->id]);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertOk()
            ->assertSee('id="session-'.$item->id.'"', false)
            ->assertSee('11:15 – 12:00')
            ->assertSee('Panel')
            ->assertSee('Main Stage')
            ->assertSee('Maya Chen')
            ->assertSee('speakers/maya.jpg', false)
            ->assertSee('>O<', false)
            ->assertSee('Are certificates a must?');
    }

    public function test_more_than_four_speakers_collapse_into_a_count(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();
        $item->syncSpeakersInOrder(Speaker::factory()->for($event)->count(6)->create()->pluck('id')->all());

        $this->get(route('agenda.show', $event).'?lang=en')->assertSee('+2 more');
    }

    public function test_workshops_appear_with_a_booking_button_and_sessions_without(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15']);
        $workshop = Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'capacity' => 20]);
        // Capacity 0 means unlimited, so "full" is a one-seat workshop whose seat is taken.
        $full = Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'capacity' => 1]);
        WorkshopBooking::create(['ticket_id' => Ticket::factory()->for($event)->create()->id, 'workshop_id' => $full->id]);

        $page = $this->get(route('agenda.show', $event).'?lang=en')->assertOk();

        $page->assertSee('id="workshop-'.$workshop->id.'"', false)
            ->assertSee(route('workshops.book', [$event, $workshop]), false)
            ->assertSee('Book your seat');
        $this->assertSame(1, substr_count($page->getContent(), 'data-book-seat'));
        $this->assertStringContainsString('20 seats left', $page->getContent());
        $page->assertDontSee(route('workshops.book', [$event, $full]), false);
    }

    public function test_break_sessions_render_as_a_slim_line(): void
    {
        $event = Event::factory()->create();
        $break = AgendaItem::factory()->for($event)->ofType('Break')->create(['title_en' => 'Coffee']);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertSee('data-break-entry="session-'.$break->id.'"', false);
    }

    public function test_every_card_has_a_matching_detail_template(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->count(2)->create();
        Workshop::factory()->for($event)->create();

        $html = $this->get(route('agenda.show', $event))->getContent();
        preg_match_all('/data-schedule-open="([a-z]+-\d+)"/', $html, $cards);

        $this->assertCount(3, $cards[1]);
        foreach ($cards[1] as $anchor) {
            $this->assertStringContainsString('id="detail-'.$anchor.'"', $html);
        }
    }

    public function test_day_tabs_and_type_filters_appear_for_a_multi_day_event(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->ofType('Keynote')->create(['day_date' => '2026-08-15']);
        AgendaItem::factory()->for($event)->ofType('Panel')->create(['day_date' => '2026-08-16']);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertSee('data-day-tab="0"', false)
            ->assertSee('data-day-tab="1"', false)
            ->assertSee('data-type-filter="all"', false)
            ->assertSee('Keynote');
    }

    public function test_agenda_page_shows_empty_state_when_no_items(): void
    {
        $event = Event::factory()->create();

        $this->get(route('agenda.show', $event).'?lang=en')->assertOk()->assertSee('No agenda items yet.');
    }

    public function test_agenda_page_links_back_to_landing_page(): void
    {
        $event = Event::factory()->create();

        $this->get(route('agenda.show', $event))->assertSee(route('landing.show', $event), false);
    }
}
```

(imports: `AgendaItem`, `Event`, `Location`, `Speaker`, `Ticket`, `Workshop`, `WorkshopBooking`, `RefreshDatabase`, `TestCase`.) If `Speaker::photoUrl()` builds a full URL from `photo_path`, the `'speakers/maya.jpg'` assertion still matches the path segment.

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Unit/EventScheduleTest.php tests/Feature/AgendaPageTest.php`
Expected: FAIL — `Class "App\Services\EventSchedule" not found`, missing card markup.

- [ ] **Step 3: ScheduleEntry and EventSchedule**

`app/Support/ScheduleEntry.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Workshop;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * One card on the public schedule: an agenda session or a scheduled workshop, read the same way
 * by the card, the pop-up and the filters.
 */
final class ScheduleEntry
{
    public function __construct(
        public readonly string $kind,
        public readonly AgendaItem|Workshop $model,
    ) {}

    public static function session(AgendaItem $item): self
    {
        return new self('session', $item);
    }

    public static function workshop(Workshop $workshop): self
    {
        return new self('workshop', $workshop);
    }

    public function anchor(): string
    {
        return $this->kind.'-'.$this->model->id;
    }

    public function title(): string
    {
        return $this->model instanceof AgendaItem ? $this->model->title() : $this->model->name();
    }

    public function description(): ?string
    {
        return $this->model->description();
    }

    public function typeLabel(): string
    {
        return $this->model instanceof AgendaItem ? $this->model->sessionType->name() : __('Workshop');
    }

    public function typeKey(): string
    {
        return $this->model instanceof AgendaItem ? 't'.$this->model->session_type_id : 'workshop';
    }

    public function isBreak(): bool
    {
        return $this->model instanceof AgendaItem && $this->model->sessionType->is_break;
    }

    public function day(): CarbonInterface
    {
        return $this->model->day_date;
    }

    public function start(): string
    {
        return $this->model->start_time->format('H:i');
    }

    public function end(): string
    {
        return $this->model->end_time->format('H:i');
    }

    public function locationName(): ?string
    {
        return $this->model->location?->name();
    }

    /** @return Collection<int, Speaker> */
    public function speakers(): Collection
    {
        return $this->model->speakers;
    }

    public function workshop(): ?Workshop
    {
        return $this->model instanceof Workshop ? $this->model : null;
    }
}
```

`app/Services/EventSchedule.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Support\ScheduleEntry;
use Illuminate\Support\Collection;

/**
 * The public schedule: agenda sessions and scheduled workshops, merged into one list per day.
 * Workshops without a day and times aren't on it (they still show on the Workshops page).
 */
class EventSchedule
{
    /**
     * @return Collection<string, Collection<int, ScheduleEntry>> keyed by Y-m-d, days ascending
     */
    public function forEvent(Event $event): Collection
    {
        $sessions = $event->agendaItems()->with(['speakers', 'sessionType', 'location'])->get()
            ->map(fn ($item) => ScheduleEntry::session($item));

        $workshops = $event->workshops()
            ->whereNotNull('day_date')->whereNotNull('start_time')->whereNotNull('end_time')
            ->with(['speakers', 'location'])->withCount('bookings')->get()
            ->map(fn ($workshop) => ScheduleEntry::workshop($workshop));

        return $sessions->concat($workshops)
            ->sortBy([
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->day()->toDateString() <=> $b->day()->toDateString(),
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->start() <=> $b->start(),
                fn (ScheduleEntry $a, ScheduleEntry $b) => ($a->kind === 'session' ? 0 : 1) <=> ($b->kind === 'session' ? 0 : 1),
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->model->id <=> $b->model->id,
            ])
            ->groupBy(fn (ScheduleEntry $entry) => $entry->day()->toDateString())
            ->map(fn (Collection $day) => $day->values());
    }
}
```

`Workshop::remainingCapacity()` counts bookings with a query each call; make it use the eager-loaded count when present so the schedule doesn't query per card:

```php
        return max(0, (int) $this->capacity - ($this->bookings_count ?? $this->bookings()->count()));
```

- [ ] **Step 4: Components**

`resources/views/components/speaker-avatar.blade.php`:

```blade
{{-- A speaker's round photo, or their initial on a coral disc when there is no photo. --}}
@props(['speaker', 'size' => 'w-10 h-10'])

@php $name = app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en; @endphp

@if($speaker->photoUrl())
    <img src="{{ $speaker->photoUrl() }}" alt="" loading="lazy" {{ $attributes->merge(['class' => $size.' rounded-full object-cover shrink-0 border border-white/15']) }}>
@else
    <span {{ $attributes->merge(['class' => $size.' rounded-full shrink-0 bg-ccs-coral/20 text-ccs-coral font-bold flex items-center justify-center']) }} aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
@endif
```

`resources/views/components/schedule-card.blade.php`:

```blade
{{-- One session or workshop on the public schedule, plus the <template> its pop-up is filled
     from. The whole card opens the pop-up; the booking button goes straight to booking. --}}
@props(['entry', 'event'])

@php
    $workshop = $entry->workshop();
    $seatsLeft = $workshop?->remainingCapacity();
    $isFull = $workshop?->isFull() ?? false;
    $speakers = $entry->speakers();
    $hasDetails = ! $entry->isBreak() || filled($entry->description());
@endphp

@if($entry->isBreak())
    <div id="{{ $entry->anchor() }}" data-break-entry="{{ $entry->anchor() }}"
         class="ccs-schedule-break md:col-span-full {{ $hasDetails ? 'cursor-pointer' : '' }}"
         @if($hasDetails) data-schedule-open="{{ $entry->anchor() }}" role="button" tabindex="0" @endif>
        <span dir="ltr" class="tabular-nums">{{ $entry->start() }} – {{ $entry->end() }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ $entry->title() }}</span>
    </div>
@else
    <article id="{{ $entry->anchor() }}" data-schedule-open="{{ $entry->anchor() }}" role="button" tabindex="0"
             aria-label="{{ $entry->title() }}"
             class="ccs-schedule-card">
        <div class="flex items-center gap-2 text-sm text-gray-300">
            <x-bi-clock class="w-4 h-4 text-ccs-gold" aria-hidden="true" />
            <span dir="ltr" class="tabular-nums font-semibold">{{ $entry->start() }} – {{ $entry->end() }}</span>
        </div>

        <span class="ccs-schedule-pill">{{ $entry->typeLabel() }}</span>

        <h3 class="font-display text-lg md:text-xl font-bold leading-snug line-clamp-3">{{ $entry->title() }}</h3>

        @if($entry->locationName())
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <x-bi-geo-alt class="w-4 h-4 text-ccs-coral" aria-hidden="true" />
                <span>{{ $entry->locationName() }}</span>
            </div>
        @endif

        @if($speakers->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                @foreach($speakers->take(4) as $speaker)
                    <li class="flex items-center gap-2.5 min-w-0">
                        <x-speaker-avatar :speaker="$speaker" />
                        <span class="text-sm font-semibold truncate">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</span>
                    </li>
                @endforeach
            </ul>
            @if($speakers->count() > 4)
                <p class="text-xs font-semibold text-gray-400">{{ __('+:count more', ['count' => $speakers->count() - 4]) }}</p>
            @endif
        @endif

        @if($workshop)
            <div class="mt-auto pt-2">
                @if($isFull)
                    <span class="ccs-schedule-book is-full" aria-disabled="true">{{ __('Full') }}</span>
                @else
                    <a href="{{ route('workshops.book', [$event, $workshop]) }}" data-book-seat class="ccs-schedule-book" @click.stop>{{ __('Book your seat') }}</a>
                @endif
            </div>
        @endif
    </article>
@endif

@if($hasDetails)
    <template id="detail-{{ $entry->anchor() }}">
        <div class="flex items-center gap-2 text-sm text-gray-300 mb-3">
            <x-bi-clock class="w-4 h-4 text-ccs-gold" aria-hidden="true" />
            <span dir="ltr" class="tabular-nums font-semibold">{{ $entry->day()->translatedFormat('j M') }} · {{ $entry->start() }} – {{ $entry->end() }}</span>
        </div>
        <span class="ccs-schedule-pill mb-3">{{ $entry->typeLabel() }}</span>
        <h2 class="font-display text-2xl font-extrabold leading-snug mb-4" data-schedule-title>{{ $entry->title() }}</h2>

        @if(filled($entry->description()))
            {{-- Sanitized on save (SanitizedRichText cast) — safe to render unescaped. --}}
            <div class="ccs-richtext text-gray-300 leading-relaxed mb-5">{!! $entry->description() !!}</div>
        @endif

        @if($entry->locationName())
            <div class="flex items-center gap-2 text-sm text-gray-300 mb-5">
                <x-bi-geo-alt class="w-4 h-4 text-ccs-coral" aria-hidden="true" />
                <span>{{ $entry->locationName() }}</span>
            </div>
        @endif

        @if($speakers->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                @foreach($speakers as $speaker)
                    <li class="flex items-center gap-3 min-w-0">
                        <x-speaker-avatar :speaker="$speaker" size="w-12 h-12" />
                        <div class="min-w-0">
                            <div class="font-semibold truncate">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</div>
                            @php $jobTitle = app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en; @endphp
                            @if($jobTitle)<div class="text-xs text-gray-400 truncate">{{ $jobTitle }}</div>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if($workshop)
            <div class="flex flex-wrap items-center gap-4">
                @if($isFull)
                    <span class="ccs-schedule-book is-full" aria-disabled="true">{{ __('Full') }}</span>
                @else
                    {{-- null = unlimited capacity: no count to show. --}}
                    @if($seatsLeft !== null)
                        <span class="text-sm text-gray-300">{{ trans_choice(':count seat left|:count seats left', $seatsLeft, ['count' => $seatsLeft]) }}</span>
                    @endif
                    <a href="{{ route('workshops.book', [$event, $workshop]) }}" class="ccs-schedule-book">{{ __('Book your seat') }}</a>
                @endif
            </div>
        @endif
    </template>
@endif
```

Note: the seats-left text lives only inside the `<template>`; `test_workshops_appear_with_a_booking_button_and_sessions_without` counts `data-book-seat` (card buttons only) and finds "20 seats left" in the template markup.

`resources/views/components/schedule-popup.blade.php`:

```blade
{{-- The one pop-up a schedule page needs, filled from the clicked card's <template>. --}}
<div x-data="schedulePopup" x-show="open" x-cloak @keydown.escape.window="close()" @schedule-open.window="show($event.detail)"
     class="fixed inset-0 z-[110]" role="dialog" aria-modal="true" :aria-labelledby="open ? 'schedule-popup-title' : null">
    <div class="absolute inset-0 bg-black/70" @click="close()" x-transition.opacity></div>
    <div class="absolute inset-0 overflow-y-auto p-4 flex items-start md:items-center justify-center" @click.self="close()">
        <div class="relative w-full max-w-2xl my-8 rounded-2xl border border-white/10 bg-ccs-black p-6 md:p-8 shadow-2xl" x-transition>
            <button type="button" x-ref="close" @click="close()" class="absolute top-4 end-4 text-gray-400 hover:text-white text-2xl leading-none" aria-label="{{ __('Close') }}">&times;</button>
            <div x-ref="body" class="pe-8"></div>
        </div>
    </div>
</div>
```

`resources/js/schedule-popup.js`:

```js
/**
 * The schedule details pop-up. Cards dispatch `schedule-open` with their anchor
 * ("session-12" / "workshop-3"); the pop-up clones that card's <template id="detail-…"> into
 * itself. The URL hash follows the open pop-up so a session can be shared, and opening the page
 * with such a hash opens it straight away. An unknown hash is ignored.
 */
export default function schedulePopup() {
    return {
        open: false,
        returnFocus: null,

        init() {
            document.addEventListener('click', (event) => {
                const card = event.target.closest('[data-schedule-open]');
                if (card && ! event.target.closest('a, button')) {
                    this.show(card.dataset.scheduleOpen);
                }
            });
            document.addEventListener('keydown', (event) => {
                const card = event.target.closest?.('[data-schedule-open]');
                if (card && (event.key === 'Enter' || event.key === ' ') && event.target === card) {
                    event.preventDefault();
                    this.show(card.dataset.scheduleOpen);
                }
            });

            const anchor = decodeURIComponent(window.location.hash.slice(1));
            if (anchor) {
                this.show(anchor, false);
            }
        },

        show(anchor, updateHash = true) {
            const template = document.getElementById(`detail-${anchor}`);
            if (! template) {
                return;
            }

            this.returnFocus = document.getElementById(anchor) ?? document.activeElement;
            this.$refs.body.replaceChildren(template.content.cloneNode(true));
            this.$refs.body.querySelector('[data-schedule-title]')?.setAttribute('id', 'schedule-popup-title');
            this.open = true;
            document.body.style.overflow = 'hidden';

            if (updateHash) {
                history.replaceState(null, '', `#${anchor}`);
            }

            this.$nextTick(() => this.$refs.close.focus());
        },

        close() {
            if (! this.open) {
                return;
            }

            this.open = false;
            document.body.style.overflow = '';
            history.replaceState(null, '', window.location.pathname + window.location.search);
            this.returnFocus?.focus?.();
        },
    };
}
```

`resources/js/app.js` — `import schedulePopup from './schedule-popup';` and, next to the other `Alpine.data(...)` registrations (before `Alpine.start()`), `Alpine.data('schedulePopup', schedulePopup);`.

Card styles — append to `resources/scss/_ccs-landing.scss`:

```scss
/* Schedule cards (resources/views/components/schedule-card.blade.php) */
.ccs-schedule-card {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  height: 100%;
  padding: 1.5rem;
  border-radius: 1.25rem;
  background: rgb(255 255 255 / 0.03);
  border: 1px solid rgb(255 255 255 / 0.1);
  cursor: pointer;
  text-align: start;
  transition: border-color 0.2s ease, transform 0.2s ease;
}

.ccs-schedule-card:hover,
.ccs-schedule-card:focus-visible {
  border-color: rgba(255, 126, 113, 0.5);
  border-color: color-mix(in srgb, var(--color-ccs-coral) 50%, transparent);
  transform: translateY(-2px);
  outline: none;
}

.ccs-schedule-pill {
  display: inline-flex;
  width: fit-content;
  padding: 0.3rem 0.8rem;
  border-radius: 9999px;
  background: rgba(250, 212, 139, 0.1);
  background: color-mix(in srgb, var(--color-ccs-gold) 10%, transparent);
  color: var(--color-ccs-gold);
  font-size: 0.78rem;
  font-weight: 700;
}

.ccs-schedule-book {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 1.25rem;
  border-radius: 0.75rem;
  background: var(--color-ccs-coral);
  color: var(--color-ccs-red);
  font-weight: 800;
  font-size: 0.9rem;
  transition: filter 0.2s ease;
}

.ccs-schedule-book:hover {
  filter: brightness(1.06);
}

.ccs-schedule-book.is-full {
  background: rgb(255 255 255 / 0.08);
  color: rgb(255 255 255 / 0.55);
  cursor: not-allowed;
}

.ccs-schedule-break {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-radius: 0.9rem;
  border: 1px dashed rgb(255 255 255 / 0.15);
  color: rgb(255 255 255 / 0.7);
  font-weight: 600;
}

@media (prefers-reduced-motion: reduce) {
  .ccs-schedule-card {
    transition: none;
  }

  .ccs-schedule-card:hover {
    transform: none;
  }
}
```

- [ ] **Step 5: Agenda controller and page**

`AgendaController::show`:

```php
    public function show(Event $event, EventSchedule $schedule): View
    {
        return view('agenda.show', ['event' => $event, 'days' => $schedule->forEvent($event)->values()]);
    }
```

`resources/views/agenda/show.blade.php` — keep the `@extends`, title, nav, back link, eyebrow/heading and footer; replace everything from `@if($days->isNotEmpty())` after the heading through the empty-state `@endif` with:

```blade
        @if($days->isNotEmpty())
            <div x-data="{ day: 0, type: 'all' }">
                @if($days->count() > 1)
                    <div class="flex gap-3 mb-6 flex-wrap" role="tablist">
                        @foreach($days as $index => $entries)
                            <button type="button" role="tab" data-day-tab="{{ $index }}" @click="day = {{ $index }}; type = 'all'"
                                    :aria-selected="day === {{ $index }}"
                                    :class="day === {{ $index }} ? 'bg-ccs-red border-ccs-red text-white' : 'border-white/10 text-gray-300'"
                                    class="px-6 py-3.5 rounded-lg border text-sm font-bold transition-colors duration-300">
                                {{ __('Day :n', ['n' => $index + 1]) }} &middot; {{ $entries->first()->day()->translatedFormat('j M') }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach($days as $index => $entries)
                    <div x-show="day === {{ $index }}" x-cloak>
                        <div class="flex gap-2 mb-8 flex-wrap">
                            <button type="button" data-type-filter="all" @click="type = 'all'" :class="type === 'all' ? 'bg-ccs-coral text-ccs-red border-ccs-coral' : 'border-white/15 text-gray-300'" class="px-4 py-2 rounded-full border text-sm font-bold">{{ __('All') }}</button>
                            @foreach($entries->unique(fn ($entry) => $entry->typeKey()) as $entry)
                                <button type="button" data-type-filter="{{ $entry->typeKey() }}" @click="type = '{{ $entry->typeKey() }}'" :class="type === '{{ $entry->typeKey() }}' ? 'bg-ccs-coral text-ccs-red border-ccs-coral' : 'border-white/15 text-gray-300'" class="px-4 py-2 rounded-full border text-sm font-bold">{{ $entry->typeLabel() }}</button>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($entries as $entry)
                                <div x-show="type === 'all' || type === '{{ $entry->typeKey() }}'" class="{{ $entry->isBreak() ? 'md:col-span-2 lg:col-span-3' : '' }}">
                                    <x-schedule-card :entry="$entry" :event="$event" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <x-schedule-popup />
        @else
            <p class="text-gray-400" data-reveal>{{ __('No agenda items yet.') }}</p>
        @endif
```

(Equal card heights come from the grid's default stretch plus `height: 100%` on `.ccs-schedule-card`, with the booking button pushed down by `mt-auto`; subgrid is not used here because filtering hides cards, which would leave subgrid rows misaligned — Ruling to ledger.)

- [ ] **Step 6: Translations**

Add: `+:count more` → `+:count آخرين`; Book your seat → احجز مقعدك; Full → مكتمل; `:count seat left|:count seats left` → `بقي مقعد واحد|بقي :count مقاعد`; Close (exists); All (exists); Workshop (exists); Day :n (exists).

- [ ] **Step 7: Run tests, build, full suite, commit**

Run: `php artisan test --compact tests/Unit/EventScheduleTest.php tests/Feature/AgendaPageTest.php tests/Feature/HubTranslationCoverageTest.php tests/Feature/FormPolishTest.php` → PASS. `npm run build` → built. `php artisan test --compact` → green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app resources lang tests
git commit -m "feat: show the agenda as cards with a details pop-up, sessions and workshops together"
```

---

### Task 5: Workshops use the same cards

**Files:**
- Modify: `resources/views/landing/partials/workshops-teaser.blade.php`, `resources/views/workshops/index.blade.php`, `resources/views/workshops/show.blade.php`, `app/Http/Controllers/WorkshopController.php`, `app/Http/Controllers/LandingPageController.php` (eager loads)
- Test: rewrite `tests/Feature/WorkshopPagesTest.php`

**Interfaces:**
- Consumes: `ScheduleEntry::workshop()`, `<x-schedule-card>`, `<x-schedule-popup>`.

- [ ] **Step 1: Write the failing tests**

Replace `tests/Feature/WorkshopPagesTest.php` body:

```php
class WorkshopPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_workshops_as_schedule_cards(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create(['name_en' => 'AI Content Workshop']);

        $this->get(route('workshops.index', $event).'?lang=en')
            ->assertOk()
            ->assertSee('AI Content Workshop')
            ->assertSee('id="workshop-'.$workshop->id.'"', false)
            ->assertSee('id="detail-workshop-'.$workshop->id.'"', false);
    }

    public function test_an_unscheduled_workshop_still_shows_on_the_workshops_page(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->unscheduled()->create(['name_en' => 'Time TBC']);

        $this->get(route('workshops.index', $event).'?lang=en')->assertOk()->assertSee('Time TBC')->assertSee('Time to be announced');
    }

    public function test_index_links_back_to_the_landing_page(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create();

        $this->get(route('workshops.index', $event))->assertSee(route('landing.show', $event), false);
    }

    public function test_the_landing_teaser_uses_the_schedule_card(): void
    {
        $event = Event::factory()->create(['status' => \App\Enums\EventStatus::Published]);
        $workshop = Workshop::factory()->for($event)->create();

        $this->get(route('landing.show', $event))->assertSee('id="workshop-'.$workshop->id.'"', false);
    }

    public function test_show_displays_the_schedule_location_and_every_speaker(): void
    {
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create(['name_en' => 'Room A']);
        $speakers = Speaker::factory()->for($event)->count(2)->sequence(['name_en' => 'Jane Creator'], ['name_en' => 'Omar Ali'])->create();
        $workshop = Workshop::factory()->for($event)->create([
            'name_en' => 'AI Content Workshop', 'day_date' => '2026-08-15', 'start_time' => '14:00', 'end_time' => '15:30', 'location_id' => $location->id,
        ]);
        $workshop->syncSpeakersInOrder($speakers->pluck('id')->all());

        $this->get(route('workshops.show', [$event, $workshop]).'?lang=en')
            ->assertOk()
            ->assertSee('14:00')->assertSee('15:30')
            ->assertSee('Room A')
            ->assertSee('Jane Creator')->assertSee('Omar Ali');
    }

    public function test_show_returns_404_for_workshop_from_another_event(): void
    {
        $event = Event::factory()->create();

        $this->get(route('workshops.show', [$event, Workshop::factory()->create()]))->assertNotFound();
    }
}
```

(imports: `Event`, `Location`, `Speaker`, `Workshop`, `RefreshDatabase`, `TestCase`.) Check `landing.show` requires a published event in `EnsureEventIsPublished`; the teaser test sets it.

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Feature/WorkshopPagesTest.php` → FAIL (no card ids).

- [ ] **Step 3: Unscheduled workshops on the card**

`ScheduleEntry::start()`/`end()`/`day()` assume a schedule. Add to `ScheduleEntry`:

```php
    public function isScheduled(): bool
    {
        return ! ($this->model instanceof Workshop) || $this->model->isScheduled();
    }
```

and in `schedule-card.blade.php` wrap both time rows (card and template) in `@if($entry->isScheduled()) … @else <span class="text-sm text-gray-400">{{ __('Time to be announced') }}</span> @endif`, guarding `$entry->day()` in the template the same way.

- [ ] **Step 4: Views and controllers**

`WorkshopController::index` → `'workshops' => $event->workshops()->with(['speakers', 'location'])->get()`. `LandingPageController` eager-load list: replace `'workshops'` with `'workshops.speakers', 'workshops.location'`.

`resources/views/workshops/index.blade.php` — replace the `<div class="grid …">…</div>` block with:

```blade
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workshops as $workshop)
                    <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                        <x-schedule-card :entry="\App\Support\ScheduleEntry::workshop($workshop)" :event="$event" />
                    </div>
                @endforeach
            </div>
            <x-schedule-popup />
```

`resources/views/landing/partials/workshops-teaser.blade.php` — the same replacement over `$event->workshops->take(3)` (keep the section heading and "See All Workshops" link), with `<x-schedule-popup />` after the grid.

`resources/views/workshops/show.blade.php` — after the `<h1>`: when `$workshop->isScheduled()` a chip `{{ $workshop->day_date->translatedFormat('j M') }} · <span dir="ltr">{{ $workshop->start_time->format('H:i') }}–{{ $workshop->end_time->format('H:i') }}</span>`; when `$workshop->location` a location line with the geo icon; replace the single speaker block with a list of `<x-speaker-avatar>` + name + job title for each of `$workshop->speakers`, and add a "Book your seat" coral link (`.ccs-schedule-book`) to `workshops.book` unless `isFull()`.

- [ ] **Step 5: Translation**

Add: Time to be announced → الموعد يُعلن لاحقًا.

- [ ] **Step 6: Run tests, full suite, commit**

Run: `php artisan test --compact tests/Feature/WorkshopPagesTest.php tests/Feature/LandingPageTest.php tests/Feature/HubTranslationCoverageTest.php` → PASS; `npm run build`; `php artisan test --compact` → green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app resources lang tests
git commit -m "feat: show workshops as schedule cards with the same details pop-up"
```

---

### Task 6: Dev database, seed data, and a browser check

**Files:** none unless a check fails.

- [ ] **Step 1: Migrate the dev database**

Run: `php artisan migrate --force` → both new migrations run. Check: `php artisan tinker --execute 'echo App\Models\SessionType::count()." types, ".App\Models\AgendaItem::whereNull("session_type_id")->count()." untyped";'` → `<5 × events> types, 0 untyped`.

- [ ] **Step 2: Build**

Run: `npm run build` → built, no errors.

- [ ] **Step 3: Browser check (Arabic and English, 1440 wide and 390 wide)**

With Playwright from the scratchpad, for `ccs-2026`:
1. As admin: add a location "Main Stage", edit a session to have 2 speakers and that location, and give a workshop a time on the same day. Screenshot the session form with the speaker picker.
2. Open `/events/ccs-2026/agenda?lang=ar`: screenshot the cards; click a type chip and confirm other cards hide; click a card and confirm the pop-up shows the description, speakers and (for the workshop) seats + "Book your seat"; press Esc and confirm it closes and the hash is cleared.
3. Open `/events/ccs-2026/agenda#session-<id>` directly → pop-up open on load. Open `#session-999999` → no pop-up, no console error.
4. Phone width: screenshot the agenda and an open pop-up.
5. Landing page workshops section and `/workshops`: cards render; "Book your seat" goes to the booking page.
Report page errors from the console; undo the test edits afterwards (or re-run `php artisan db:seed --class=CcsEventSeeder` only if the user agrees — do not reseed without asking).

- [ ] **Step 4: Final full suite**

Run: `php artisan test --compact` → all green; report the count.

# Admin Roles and Permissions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the fixed `admin` / `check_in` user roles with admin-managed roles built from a fixed list of per-section permissions, shipped with five starting roles.

**Architecture:** A `Permission` enum lists every grantable admin section and the route-name patterns it unlocks; a `roles` table stores named roles with a JSON list of permission values plus a locked built-in Admin role. One `permission` middleware resolves the current route to a permission (unmapped routes are Admin-only), and the sidebar, login redirect, Staff page and a new Roles page all read the same model.

**Tech Stack:** Laravel 12, PHP 8.3, MySQL (dev/live) and in-memory SQLite (tests), Blade + Alpine, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-09-26-admin-roles-permissions-design.md`

## Global Constraints

- No new Composer or npm dependencies (no `spatie/laravel-permission`).
- The built-in Admin role (`is_system = true`) always has every permission; it can't be edited or deleted.
- Staff (`admin.staff.*`) and Roles (`admin.roles.*`) are Admin-only and never grantable.
- Any `admin.*` or `check-in.*` route not mapped to a permission is Admin-only (deny by default).
- One permission per admin section; a role applies to all events; a user has exactly one role.
- The starting roles are created by a migration (not a seeder) so `php artisan migrate --force` sets them up on the live server.
- Migrations reference permissions as plain strings, never the enum.
- Every user-visible string goes through `__()` with an Arabic entry in `lang/ar.json` (the translation coverage test enforces this).
- Run `vendor/bin/pint --dirty --format agent` after PHP changes. Tests are PHPUnit classes; run with `php artisan test --compact`.
- Commits are made under the user's own git identity with no `Co-Authored-By` line.
- In this Windows/Git Bash environment, heredocs strip backslashes: write multi-line scripts or PHP files with the file-writing tool, not `cat <<EOF`.

## Review Focus

1. **A non-admin role holding every permission** must still get 403 on Staff and Roles, including direct POST/PUT/DELETE — Task 3 test `test_every_permission_still_does_not_open_staff_or_roles`.
2. **A role with no permissions ticked** must log in to the "no access yet" page, not an error, and get 403 elsewhere — Task 4 test `test_a_role_without_permissions_lands_on_the_no_access_page`.
3. **A per-event permission when no events exist** must not crash the login redirect; it skips to the next allowed section or the no-access page — Task 4 test `test_login_skips_event_sections_when_there_are_no_events`.
4. **Deleting a role that still has staff** must be refused and leave the staff on it; the Admin role can't be edited or deleted by URL — Task 5 tests `test_a_role_with_staff_cannot_be_deleted` and `test_the_admin_role_is_locked`.
5. **A tampered permission value** (not in the enum) must be rejected, not stored — Task 5 test `test_unknown_permissions_are_rejected`.

---

## File Structure

| File | Responsibility |
|---|---|
| `app/Enums/Permission.php` (new) | Every grantable permission: label, group, route patterns, entry route |
| `app/Support/AdminPermissions.php` (new) | Route name → permission lookup, the Admin-only list, and where a user lands after login |
| `app/Models/Role.php` (new) | A named role; `grants(Permission)` |
| `database/factories/RoleFactory.php` (new) | Test roles with chosen permissions |
| `database/migrations/*_create_roles_table.php` (new) | `roles` table, starting roles, move `users.role` → `users.role_id` |
| `app/Models/User.php` | `role()` relation, `hasPermission()`, `isAdmin()`, `admins()` scope |
| `database/factories/UserFactory.php` | Default Admin role; `checkIn()` and `withPermissions()` states |
| `database/seeders/AdminUserSeeder.php` | Seeds the admin with the Admin role |
| `app/Enums/UserRole.php` | **Deleted** |
| `app/Http/Middleware/EnsureUserHasPermission.php` (new) | The `permission` middleware |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | **Deleted** |
| `bootstrap/app.php` | Registers the `permission` alias instead of `admin` |
| `routes/web.php` | `permission` on the admin and check-in groups; Roles resource; no-access page |
| `app/Http/Controllers/Admin/AuthController.php` | Login redirect via `AdminPermissions::homeFor()` |
| `app/Http/Controllers/Admin/StaffController.php`, `app/Http/Requests/Admin/StaffRequest.php`, `resources/views/admin/staff/*` | Role picker from the `roles` table |
| `app/Http/Controllers/Admin/RoleController.php`, `app/Http/Requests/Admin/RoleRequest.php`, `resources/views/admin/roles/*` (new) | The Roles page and permission matrix |
| `resources/views/admin/partials/sidebar.blade.php` | Links filtered by permission |
| `resources/views/admin/no-access.blade.php` (new) | Shown to a role with nothing ticked |
| `lang/en.json`, `lang/ar.json` | New strings |

---

### Task 1: Permission enum and route map

**Files:**
- Create: `app/Enums/Permission.php`
- Create: `app/Support/AdminPermissions.php` (lookup methods only; `homeFor()` arrives in Task 4)
- Test: `tests/Unit/AdminPermissionsTest.php`

**Interfaces:**
- Produces: `enum App\Enums\Permission: string` with cases `Dashboard`, `Events`, `LandingPage`, `AgendaWorkshops`, `Speakers`, `SpeakerRequests`, `Sponsors`, `SponsorRequests`, `TicketSetup`, `TicketRequests`, `Invitations`, `DiscountCoupons`, `EventReports`, `Inbox`, `RegistrationDesk`, `HubSite`, `PlatformReport`; methods `label(): string`, `group(): string`, `routes(): list<string>`, `entryRoute(): string`, `needsEvent(): bool`, `static grouped(): array<string, list<Permission>>`.
- Produces: `App\Support\AdminPermissions::for(string $routeName): ?Permission`, `AdminPermissions::isAdminOnly(string $routeName): bool`, `AdminPermissions::ADMIN_ONLY` (`list<string>`).

- [ ] **Step 1: Write the failing test**

`php artisan make:test --unit --phpunit AdminPermissionsTest --no-interaction`, then replace its body:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Permission;
use App\Support\AdminPermissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminPermissionsTest extends TestCase
{
    /** @return array<string, array{string, Permission}> */
    public static function mappedRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard', Permission::Dashboard],
            'event list' => ['admin.events.index', Permission::Events],
            'event edit' => ['admin.events.edit', Permission::Events],
            'landing content' => ['admin.events.content.edit', Permission::LandingPage],
            'reels' => ['admin.events.reels.index', Permission::LandingPage],
            'agenda' => ['admin.events.agenda-items.create', Permission::AgendaWorkshops],
            'workshop bookings' => ['admin.events.workshops.bookings', Permission::AgendaWorkshops],
            'speakers' => ['admin.events.speakers.index', Permission::Speakers],
            'speaker requests' => ['admin.events.speaker-requests.update-status', Permission::SpeakerRequests],
            'sponsors' => ['admin.events.sponsors.index', Permission::Sponsors],
            'sponsor tiers' => ['admin.events.sponsor-tiers.store', Permission::Sponsors],
            'sponsor requests' => ['admin.events.sponsor-requests.index', Permission::SponsorRequests],
            'ticket types' => ['admin.events.ticket-types.index', Permission::TicketSetup],
            'influencer settings' => ['admin.events.influencer-categories.update-settings', Permission::TicketSetup],
            'ticket requests' => ['admin.events.ticket-requests.answers.download', Permission::TicketRequests],
            'invitations' => ['admin.events.invitations.revoke', Permission::Invitations],
            'invitation requests' => ['admin.events.invitation-requests.index', Permission::Invitations],
            'coupons' => ['admin.events.discount-coupons.index', Permission::DiscountCoupons],
            'report export' => ['admin.events.reports.export', Permission::EventReports],
            'newsletter' => ['admin.events.newsletter-subscribers.index', Permission::Inbox],
            'desk scan' => ['check-in.scan', Permission::RegistrationDesk],
            'hub cards' => ['admin.audience-tabs.cards.edit', Permission::HubSite],
            'platform report' => ['admin.reports.show', Permission::PlatformReport],
        ];
    }

    #[DataProvider('mappedRoutes')]
    public function test_each_route_resolves_to_its_permission(string $route, Permission $expected): void
    {
        $this->assertSame($expected, AdminPermissions::for($route));
    }

    public function test_staff_roles_and_unknown_routes_resolve_to_nothing(): void
    {
        $this->assertNull(AdminPermissions::for('admin.staff.index'));
        $this->assertNull(AdminPermissions::for('admin.roles.update'));
        $this->assertNull(AdminPermissions::for('admin.something-new.index'));
    }

    public function test_staff_and_roles_are_admin_only(): void
    {
        $this->assertTrue(AdminPermissions::isAdminOnly('admin.staff.edit'));
        $this->assertTrue(AdminPermissions::isAdminOnly('admin.roles.index'));
        $this->assertFalse(AdminPermissions::isAdminOnly('admin.events.speakers.index'));
    }

    public function test_every_permission_belongs_to_exactly_one_group(): void
    {
        $grouped = array_merge(...array_values(Permission::grouped()));

        $this->assertCount(count(Permission::cases()), $grouped);
    }

    public function test_per_event_permissions_know_they_need_an_event(): void
    {
        $this->assertTrue(Permission::TicketRequests->needsEvent());
        $this->assertFalse(Permission::Events->needsEvent());
        $this->assertFalse(Permission::RegistrationDesk->needsEvent());
        $this->assertFalse(Permission::HubSite->needsEvent());
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --compact tests/Unit/AdminPermissionsTest.php`
Expected: FAIL — `Class "App\Enums\Permission" not found`.

- [ ] **Step 3: Write the enum**

Create `app/Enums/Permission.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Everything a staff role can be allowed to open, one per admin section.
 *
 * The list lives in code because each permission guards real routes (routes()); the admin only
 * combines them into roles on the Roles page. Staff and Roles themselves are never listed here —
 * they stay with the built-in Admin role (App\Support\AdminPermissions::ADMIN_ONLY).
 */
enum Permission: string
{
    case Dashboard = 'dashboard';
    case Events = 'events';
    case LandingPage = 'landing_page';
    case AgendaWorkshops = 'agenda_workshops';
    case Speakers = 'speakers';
    case SpeakerRequests = 'speaker_requests';
    case Sponsors = 'sponsors';
    case SponsorRequests = 'sponsor_requests';
    case TicketSetup = 'ticket_setup';
    case TicketRequests = 'ticket_requests';
    case Invitations = 'invitations';
    case DiscountCoupons = 'discount_coupons';
    case EventReports = 'event_reports';
    case Inbox = 'inbox';
    case RegistrationDesk = 'registration_desk';
    case HubSite = 'hub_site';
    case PlatformReport = 'platform_report';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => __('Dashboard'),
            self::Events => __('Events'),
            self::LandingPage => __('Landing page'),
            self::AgendaWorkshops => __('Agenda and workshops'),
            self::Speakers => __('Speakers'),
            self::SpeakerRequests => __('Speaker requests'),
            self::Sponsors => __('Sponsors'),
            self::SponsorRequests => __('Sponsor requests'),
            self::TicketSetup => __('Ticket setup'),
            self::TicketRequests => __('Ticket requests'),
            self::Invitations => __('Invitations'),
            self::DiscountCoupons => __('Discount coupons'),
            self::EventReports => __('Event reports'),
            self::Inbox => __('Inbox'),
            self::RegistrationDesk => __('Registration desk'),
            self::HubSite => __('Hub site'),
            self::PlatformReport => __('Platform report'),
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Dashboard => __('Overview'),
            self::Events, self::LandingPage, self::AgendaWorkshops => __('Event setup'),
            self::Speakers, self::SpeakerRequests, self::Sponsors, self::SponsorRequests => __('Speakers and sponsors'),
            self::TicketSetup, self::TicketRequests, self::Invitations, self::DiscountCoupons, self::EventReports, self::Inbox => __('Tickets and sales'),
            self::RegistrationDesk => __('Event day'),
            self::HubSite, self::PlatformReport => __('Creators Hub'),
        };
    }

    /**
     * Route-name patterns (Str::is) this permission opens. The event CRUD routes are listed by
     * exact name: every per-event section also starts with "admin.events.".
     *
     * @return list<string>
     */
    public function routes(): array
    {
        return match ($this) {
            self::Dashboard => ['admin.dashboard'],
            self::Events => ['admin.events.index', 'admin.events.create', 'admin.events.store', 'admin.events.edit', 'admin.events.update', 'admin.events.destroy'],
            self::LandingPage => ['admin.events.content.*', 'admin.events.reels.*', 'admin.events.gallery-photos.*', 'admin.events.testimonials.*', 'admin.events.faqs.*'],
            self::AgendaWorkshops => ['admin.events.agenda-items.*', 'admin.events.workshops.*'],
            self::Speakers => ['admin.events.speakers.*'],
            self::SpeakerRequests => ['admin.events.speaker-requests.*'],
            self::Sponsors => ['admin.events.sponsors.*', 'admin.events.sponsor-tiers.*'],
            self::SponsorRequests => ['admin.events.sponsor-requests.*'],
            self::TicketSetup => ['admin.events.ticket-types.*', 'admin.events.request-form-fields.*', 'admin.events.influencer-categories.*'],
            self::TicketRequests => ['admin.events.ticket-requests.*'],
            self::Invitations => ['admin.events.invitations.*', 'admin.events.invitation-requests.*'],
            self::DiscountCoupons => ['admin.events.discount-coupons.*'],
            self::EventReports => ['admin.events.reports.*'],
            self::Inbox => ['admin.events.contact-messages.*', 'admin.events.newsletter-subscribers.*'],
            self::RegistrationDesk => ['check-in.*'],
            self::HubSite => ['admin.site-content.*', 'admin.site-faqs.*', 'admin.hero-slides.*', 'admin.hub-partners.*', 'admin.audience-tabs.*'],
            self::PlatformReport => ['admin.reports.*'],
        };
    }

    /**
     * The page a person with only this permission starts on.
     */
    public function entryRoute(): string
    {
        return match ($this) {
            self::Dashboard => 'admin.dashboard',
            self::Events => 'admin.events.index',
            self::LandingPage => 'admin.events.content.edit',
            self::AgendaWorkshops => 'admin.events.agenda-items.index',
            self::Speakers => 'admin.events.speakers.index',
            self::SpeakerRequests => 'admin.events.speaker-requests.index',
            self::Sponsors => 'admin.events.sponsors.index',
            self::SponsorRequests => 'admin.events.sponsor-requests.index',
            self::TicketSetup => 'admin.events.ticket-types.index',
            self::TicketRequests => 'admin.events.ticket-requests.index',
            self::Invitations => 'admin.events.invitations.index',
            self::DiscountCoupons => 'admin.events.discount-coupons.index',
            self::EventReports => 'admin.events.reports.show',
            self::Inbox => 'admin.events.contact-messages.index',
            self::RegistrationDesk => 'check-in.events',
            self::HubSite => 'admin.site-content.index',
            self::PlatformReport => 'admin.reports.show',
        };
    }

    /**
     * Whether the entry page belongs to one event and so needs an event to open.
     */
    public function needsEvent(): bool
    {
        return $this !== self::Events && str_starts_with($this->entryRoute(), 'admin.events.');
    }

    /**
     * The permissions arranged for the Roles page, keyed by their translated group name.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }
}
```

- [ ] **Step 4: Write the lookup class**

Create `app/Support/AdminPermissions.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Permission;
use Illuminate\Support\Str;

/**
 * Which permission opens which admin route.
 *
 * Deny by default: a route that no permission lists is open to the built-in Admin role only.
 * ADMIN_ONLY names the routes that are Admin-only on purpose, so the route-coverage test can tell
 * a decision from an oversight.
 */
final class AdminPermissions
{
    /** @var list<string> */
    public const ADMIN_ONLY = ['admin.staff.*', 'admin.roles.*'];

    public static function for(string $routeName): ?Permission
    {
        foreach (Permission::cases() as $permission) {
            if (Str::is($permission->routes(), $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    public static function isAdminOnly(string $routeName): bool
    {
        return Str::is(self::ADMIN_ONLY, $routeName);
    }
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Unit/AdminPermissionsTest.php`
Expected: PASS (27 tests).

- [ ] **Step 6: Add the translations**

Add to `lang/en.json` (key = value) and `lang/ar.json`. Skip any key already present in a file.

| Key | Arabic |
|---|---|
| Landing page | الصفحة الرئيسية |
| Agenda and workshops | الجدول وورش العمل |
| Speaker requests | طلبات المتحدثين |
| Sponsor requests | طلبات الرعاية |
| Ticket setup | إعداد التذاكر |
| Ticket requests | طلبات التذاكر |
| Discount coupons | أكواد الخصم |
| Event reports | تقارير الفعالية |
| Inbox | صندوق الوارد |
| Registration desk | مكتب التسجيل |
| Hub site | موقع Creators Hub |
| Platform report | تقرير المنصة |
| Overview | نظرة عامة |
| Event setup | إعداد الفعالية |
| Speakers and sponsors | المتحدثون والرعاة |
| Tickets and sales | التذاكر والمبيعات |
| Event day | يوم الفعالية |

(`Dashboard`, `Events`, `Speakers`, `Sponsors`, `Invitations`, `Creators Hub` already exist — confirm with `grep`.)

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/Permission.php app/Support/AdminPermissions.php tests/Unit/AdminPermissionsTest.php lang/en.json lang/ar.json
git commit -m "feat: list the admin permissions and the routes each one opens"
```

---

### Task 2: Roles table, Role model, and moving users across

**Files:**
- Create: `database/migrations/<timestamp>_create_roles_table.php` (via `php artisan make:migration create_roles_table --no-interaction`)
- Create: `app/Models/Role.php` (via `php artisan make:model Role --factory --no-interaction`)
- Create/replace: `database/factories/RoleFactory.php`
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`, `database/seeders/AdminUserSeeder.php`
- Modify: `app/Http/Controllers/Admin/StaffController.php`, `app/Http/Requests/Admin/StaffRequest.php`, `resources/views/admin/staff/form.blade.php`, `resources/views/admin/staff/index.blade.php`, `app/Http/Controllers/Admin/AuthController.php` (only to stop using the enum)
- Delete: `app/Enums/UserRole.php`
- Test: `tests/Feature/Admin/RoleMigrationTest.php` (new), `tests/Feature/Admin/StaffCrudTest.php` (rewrite)

**Interfaces:**
- Consumes: `Permission` (Task 1).
- Produces: `App\Models\Role` — attributes `name` (string), `permissions` (array of strings), `is_system` (bool); `static system(): Role`; `grants(Permission $permission): bool`; `users(): HasMany`.
- Produces: `User::role(): BelongsTo`, `User::hasPermission(Permission $permission): bool`, `User::isAdmin(): bool`, scope `User::admins()`.
- Produces: `UserFactory` default = Admin role; states `checkIn()` (the "Registration Desk" role) and `withPermissions(Permission ...$permissions)`.
- Produces: `RoleFactory` state `withPermissions(Permission ...$permissions)`.

- [ ] **Step 1: Write the failing migration test**

Create `tests/Feature/Admin/RoleMigrationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_starting_roles_exist_with_their_permissions(): void
    {
        $this->assertTrue(Role::system()->is_system);
        $this->assertSame('Admin', Role::system()->name);

        $roles = Role::where('is_system', false)->pluck('permissions', 'name');

        $this->assertEqualsCanonicalizing(['Speakers', 'Sponsors', 'Sales', 'Project Manager', 'Registration Desk'], $roles->keys()->all());
        $this->assertEqualsCanonicalizing(['speakers', 'speaker_requests'], $roles['Speakers']);
        $this->assertEqualsCanonicalizing(['sponsors', 'sponsor_requests'], $roles['Sponsors']);
        $this->assertEqualsCanonicalizing(['ticket_requests', 'invitations', 'discount_coupons', 'event_reports'], $roles['Sales']);
        $this->assertEqualsCanonicalizing(
            ['dashboard', 'speakers', 'speaker_requests', 'sponsors', 'sponsor_requests', 'ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
            $roles['Project Manager'],
        );
        $this->assertEqualsCanonicalizing(['registration_desk'], $roles['Registration Desk']);
    }

    /**
     * Rolls just this migration back, recreates staff with the old role column, and migrates
     * again. If a later migration comes to depend on the roles table, this rollback will need
     * revisiting.
     */
    public function test_existing_staff_move_to_the_matching_roles(): void
    {
        $migration = collect(glob(database_path('migrations/*_create_roles_table.php')))->sole();
        $this->artisan('migrate:rollback', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $now = now();
        DB::table('users')->insert([
            ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'x', 'role' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Door', 'email' => 'door@example.com', 'password' => 'x', 'role' => 'check_in', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odd', 'email' => 'odd@example.com', 'password' => 'x', 'role' => 'mystery', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->artisan('migrate', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $this->assertTrue(User::where('email', 'boss@example.com')->sole()->isAdmin());

        foreach (['door@example.com', 'odd@example.com'] as $email) {
            $user = User::where('email', $email)->sole();
            $this->assertSame('Registration Desk', $user->role->name);
            $this->assertTrue($user->hasPermission(Permission::RegistrationDesk));
            $this->assertFalse($user->hasPermission(Permission::TicketRequests));
        }
    }

    public function test_the_admin_role_grants_everything(): void
    {
        $admin = User::factory()->create();

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($admin->hasPermission($permission), $permission->value);
        }
        $this->assertTrue($admin->isAdmin());
    }

    public function test_a_custom_role_grants_only_its_permissions(): void
    {
        $sales = User::factory()->withPermissions(Permission::TicketRequests, Permission::Invitations)->create();

        $this->assertTrue($sales->hasPermission(Permission::TicketRequests));
        $this->assertFalse($sales->hasPermission(Permission::Speakers));
        $this->assertFalse($sales->isAdmin());
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/RoleMigrationTest.php`
Expected: FAIL — `Class "App\Models\Role" not found`.

- [ ] **Step 3: Write the migration**

Run `php artisan make:migration create_roles_table --no-interaction` and replace the file's contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff roles become records the admin manages, built from the permissions in
 * App\Enums\Permission. Permissions are written as plain strings here so this migration keeps
 * working however the enum changes later.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const STARTING_ROLES = [
        'Speakers' => ['speakers', 'speaker_requests'],
        'Sponsors' => ['sponsors', 'sponsor_requests'],
        'Sales' => ['ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
        'Project Manager' => ['dashboard', 'speakers', 'speaker_requests', 'sponsors', 'sponsor_requests', 'ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
        'Registration Desk' => ['registration_desk'],
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->json('permissions');
            // The built-in Admin role: locked, and always granted everything.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert(['name' => 'Admin', 'permissions' => '[]', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now]);

        foreach (self::STARTING_ROLES as $name => $permissions) {
            DB::table('roles')->insert(['name' => $name, 'permissions' => json_encode($permissions), 'is_system' => false, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->restrictOnDelete();
        });

        $adminRoleId = DB::table('roles')->where('is_system', true)->value('id');
        $deskRoleId = DB::table('roles')->where('name', 'Registration Desk')->value('id');

        DB::table('users')->where('role', 'admin')->update(['role_id' => $adminRoleId]);
        // Check-in staff, and anyone with a value this app never used, get the least access.
        DB::table('users')->whereNull('role_id')->update(['role_id' => $deskRoleId]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email');
        });

        $adminRoleIds = DB::table('roles')->where('is_system', true)->pluck('id');
        DB::table('users')->whereIn('role_id', $adminRoleIds)->update(['role' => 'admin']);
        DB::table('users')->whereNotIn('role_id', $adminRoleIds)->update(['role' => 'check_in']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
```

- [ ] **Step 4: Write the Role model and factory**

Run `php artisan make:model Role --factory --no-interaction`, then replace `app/Models/Role.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staff role: a name and the permissions it grants. The built-in Admin role (is_system) is
 * locked and grants everything regardless of its stored list.
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = ['name', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public static function system(): self
    {
        return self::query()->where('is_system', true)->firstOrFail();
    }

    public function grants(Permission $permission): bool
    {
        return $this->is_system || in_array($permission->value, $this->permissions ?? [], true);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

Replace `database/factories/RoleFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'permissions' => [],
            'is_system' => false,
        ];
    }

    public function withPermissions(Permission ...$permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => array_map(fn (Permission $permission) => $permission->value, $permissions),
        ]);
    }
}
```

- [ ] **Step 5: Update the User model**

In `app/Models/User.php`: replace `use App\Enums\UserRole;` with `use App\Enums\Permission;` and add `use Illuminate\Database\Eloquent\Builder;` and `use Illuminate\Database\Eloquent\Relations\BelongsTo;`; in `$fillable` replace `'role'` with `'role_id'`; remove `'role' => UserRole::class,` from `casts()`; replace `isAdmin()` with:

```php
    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->role?->grants($permission) ?? false;
    }

    /**
     * Holds the built-in Admin role: every page, plus Staff and Roles.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->role?->is_system;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeAdmins(Builder $query): void
    {
        $query->whereHas('role', fn (Builder $role) => $role->where('is_system', true));
    }
```

- [ ] **Step 6: Update the User factory and the admin seeder**

In `database/factories/UserFactory.php`: replace `use App\Enums\UserRole;` with `use App\Enums\Permission;` and `use App\Models\Role;`; in `definition()` replace `'role' => UserRole::Admin,` with `'role_id' => fn () => Role::system()->id,`; replace the `checkIn()` state and add `withPermissions()`:

```php
    /**
     * The "Registration Desk" starting role: the check-in desk and nothing else.
     */
    public function checkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => fn () => Role::firstOrCreate(
                ['name' => 'Registration Desk'],
                ['permissions' => [Permission::RegistrationDesk->value]],
            )->id,
        ]);
    }

    public function withPermissions(Permission ...$permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->withPermissions(...$permissions),
        ]);
    }
```

In `database/seeders/AdminUserSeeder.php`, add `use App\Models\Role;` and add `'role_id' => Role::system()->id,` to the `updateOrCreate` values array (next to `'email_verified_at'`).

- [ ] **Step 7: Move the Staff page and login off the enum**

`app/Http/Controllers/Admin/StaffController.php` — replace `use App\Enums\UserRole;` with `use App\Models\Role;` and change:

```php
    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::with('role')->orderBy('role_id')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['user' => new User, 'roles' => $this->roles()]);
    }
```

```php
    public function edit(User $user): View
    {
        return view('admin.staff.form', ['user' => $user, 'roles' => $this->roles()]);
    }
```

In `destroy()` replace `User::where('role', UserRole::Admin)->count() <= 1` with `User::admins()->count() <= 1`. Add at the end of the class:

```php
    /**
     * The Admin role first, then the rest by name.
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function roles(): \Illuminate\Support\Collection
    {
        return Role::orderByDesc('is_system')->orderBy('name')->get();
    }
```

`app/Http/Requests/Admin/StaffRequest.php` — replace `use App\Enums\UserRole;` with `use App\Models\Role;` and replace the `'role' => [...]` rule with:

```php
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($staff): void {
                    $newRole = Role::find($value);

                    if ($staff === null || $newRole === null || $newRole->is_system || ! $staff->isAdmin()) {
                        return;
                    }

                    // Taking admin away from yourself would lock you out of this page, and taking
                    // it from the last admin would leave nobody able to manage the site.
                    if ($staff->is($this->user())) {
                        $fail(__('You can\'t remove your own admin access.'));
                    } elseif (User::admins()->count() <= 1) {
                        $fail(__('At least one admin is required.'));
                    }
                },
            ],
```

Add to the class:

```php
    public function attributes(): array
    {
        return ['role_id' => __('Role')];
    }
```

`resources/views/admin/staff/form.blade.php` — replace the role select with:

```blade
        <x-admin.field type="select" name="role_id" label="{{ __('Role') }}" required>
            <option value="">{{ __('Choose a role') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </x-admin.field>
```

`resources/views/admin/staff/index.blade.php` — replace `{{ $member->role->label() }}` with `{{ $member->role?->name }}` and replace the explanation paragraph with:

```blade
    <p class="mb-5 text-sm text-hub-dark/60">{{ __('What each person can open depends on their role. Admins can use every page, including Staff and Roles.') }}</p>
```

`app/Http/Controllers/Admin/AuthController.php` needs no change in this task (it only calls `isAdmin()`).

Delete `app/Enums/UserRole.php`. Confirm nothing else uses it: `grep -rn "UserRole" app database resources routes tests` — only `tests/Feature/Admin/StaffCrudTest.php` should remain, rewritten next.

- [ ] **Step 8: Rewrite the Staff CRUD test for role_id**

Replace `tests/Feature/Admin/StaffCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCrudTest extends TestCase
{
    use RefreshDatabase;

    private function deskRole(): Role
    {
        return Role::where('name', 'Registration Desk')->sole();
    }

    public function test_admin_sees_every_staff_member_with_their_role(): void
    {
        $admin = User::factory()->create(['name' => 'Main Admin']);
        User::factory()->checkIn()->create(['name' => 'Door Staff']);

        $this->actingAs($admin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Main Admin')
            ->assertSee('Door Staff')
            ->assertSee('Registration Desk');
    }

    public function test_the_role_picker_lists_every_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSeeInOrder(['Admin', 'Project Manager', 'Registration Desk', 'Sales']);
    }

    public function test_admin_can_add_a_staff_member_with_a_role(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'door@example.com',
            'role_id' => $this->deskRole()->id,
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'door@example.com')->firstOrFail();
        $this->assertTrue($staff->role->is($this->deskRole()));
        $this->assertTrue(Hash::check('a-long-password', $staff->password));
    }

    public function test_new_staff_need_a_unique_email_a_real_role_and_a_confirmed_password_of_eight_characters(): void
    {
        $admin = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'taken@example.com',
            'role_id' => 99999,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'role_id', 'password']);
    }

    public function test_editing_keeps_the_password_when_left_blank(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();
        $originalHash = $staff->password;

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Renamed',
            'email' => $staff->email,
            'role_id' => $staff->role_id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertSame('Renamed', $staff->fresh()->name);
        $this->assertSame($originalHash, $staff->fresh()->password);
    }

    public function test_editing_can_change_the_password_and_role(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role_id' => Role::system()->id,
            'password' => 'another-long-password',
            'password_confirmation' => 'another-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertTrue($staff->fresh()->isAdmin());
        $this->assertTrue(Hash::check('another-long-password', $staff->fresh()->password));
    }

    public function test_admin_can_remove_a_staff_member(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $staff))
            ->assertRedirect(route('admin.staff.index'));

        $this->assertModelMissing($staff);
    }

    public function test_an_admin_cannot_remove_themselves(): void
    {
        $admin = User::factory()->create();
        User::factory()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create();
        User::factory()->checkIn()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $this->deskRole()->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_an_admin_can_be_demoted_while_another_admin_remains(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $otherAdmin), [
            'name' => $otherAdmin->name,
            'email' => $otherAdmin->email,
            'role_id' => $this->deskRole()->id,
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertFalse($otherAdmin->fresh()->isAdmin());
    }
}
```

- [ ] **Step 9: Add translations**

Add to both JSON files (skip keys that exist): `Choose a role` → `اختر دورًا`; `What each person can open depends on their role. Admins can use every page, including Staff and Roles.` → `ما يمكن لكل شخص فتحه يعتمد على دوره. يمكن للمسؤولين استخدام كل الصفحات، بما فيها الفريق والأدوار.`. Remove the now-unused `Admins can use every page. Check-in staff can only open the registration desk to scan tickets.` and `Check-in staff` keys from both files only if `grep -rn` shows nothing else uses them.

- [ ] **Step 10: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/RoleMigrationTest.php tests/Feature/Admin/StaffCrudTest.php tests/Feature/Admin/StaffRolesTest.php tests/Feature/DatabaseSeederTest.php`
Expected: PASS. (StaffRolesTest still passes: the `admin` middleware now reads `isAdmin()` from the role.)

Then the whole suite: `php artisan test --compact` — expected all green.

- [ ] **Step 11: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app database resources tests lang
git commit -m "feat: store staff roles in a roles table with five starting roles"
```

---

### Task 3: The permission middleware and route coverage

**Files:**
- Create: `app/Http/Middleware/EnsureUserHasPermission.php` (via `php artisan make:middleware EnsureUserHasPermission --no-interaction`)
- Delete: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Modify: `bootstrap/app.php`, `routes/web.php`
- Test: `tests/Feature/Admin/PermissionAccessTest.php` (new); `tests/Feature/Admin/StaffRolesTest.php` (unchanged, must keep passing)

**Interfaces:**
- Consumes: `AdminPermissions::for()`, `AdminPermissions::isAdminOnly()`, `AdminPermissions::ADMIN_ONLY` (Task 1); `User::hasPermission()`, `User::isAdmin()`, `UserFactory::withPermissions()`, `checkIn()` (Task 2).
- Produces: middleware alias `permission`; route `admin.no-access` (GET `/admin/no-access`, `auth` only) rendering view `admin.no-access` (the view itself arrives in Task 4 — this task registers the route with `Route::view`, so create a minimal placeholder view now, see Step 5).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/PermissionAccessTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithRole(string $name): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $name)->sole()->id]);
    }

    /**
     * Every admin and desk route must be tied to a permission or be Admin-only on purpose, so a
     * new page can't ship without someone deciding who may open it.
     */
    public function test_every_admin_route_is_mapped_or_admin_only(): void
    {
        $unmapped = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name) => $name !== null && Str::startsWith($name, ['admin.', 'check-in.']))
            ->reject(fn (string $name) => in_array($name, ['admin.login', 'admin.logout', 'admin.no-access'], true))
            ->reject(fn (string $name) => AdminPermissions::for($name) !== null || AdminPermissions::isAdminOnly($name))
            ->values()
            ->all();

        $this->assertSame([], $unmapped, 'Map these routes to a Permission or add them to AdminPermissions::ADMIN_ONLY.');
    }

    public function test_sales_opens_ticket_work_and_nothing_else(): void
    {
        $sales = $this->staffWithRole('Sales');
        $event = Event::factory()->create();

        $this->actingAs($sales)->get(route('admin.events.ticket-requests.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.invitations.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.discount-coupons.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.reports.show', $event))->assertOk();

        $this->actingAs($sales)->get(route('admin.events.speakers.index', $event))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.events.ticket-types.index', $event))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($sales)->get(route('check-in.events'))->assertForbidden();
    }

    public function test_speakers_and_sponsors_roles_stay_in_their_lane(): void
    {
        $event = Event::factory()->create();
        $speakers = $this->staffWithRole('Speakers');
        $sponsors = $this->staffWithRole('Sponsors');

        $this->actingAs($speakers)->get(route('admin.events.speaker-requests.index', $event))->assertOk();
        $this->actingAs($speakers)->get(route('admin.events.speakers.index', $event))->assertOk();
        $this->actingAs($speakers)->get(route('admin.events.sponsor-requests.index', $event))->assertForbidden();

        $this->actingAs($sponsors)->get(route('admin.events.sponsor-requests.index', $event))->assertOk();
        $this->actingAs($sponsors)->get(route('admin.events.sponsor-tiers.index', $event))->assertOk();
        $this->actingAs($sponsors)->get(route('admin.events.ticket-requests.index', $event))->assertForbidden();
    }

    public function test_project_manager_sees_the_three_teams_and_the_dashboard(): void
    {
        $manager = $this->staffWithRole('Project Manager');
        $event = Event::factory()->create();

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.speaker-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.sponsor-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.ticket-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.content.edit', $event))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.staff.index'))->assertForbidden();
    }

    public function test_a_forbidden_action_changes_nothing(): void
    {
        $speakers = $this->staffWithRole('Speakers');
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create(['status' => TicketStatus::Pending]);

        $this->actingAs($speakers)
            ->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertForbidden();

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
    }

    public function test_every_permission_still_does_not_open_staff_or_roles(): void
    {
        $everything = User::factory()->withPermissions(...Permission::cases())->create();
        $other = User::factory()->checkIn()->create();

        $this->actingAs($everything)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($everything)->get(route('admin.staff.edit', $other))->assertForbidden();
        $this->actingAs($everything)->delete(route('admin.staff.destroy', $other))->assertForbidden();
        $this->assertModelExists($other);
    }

    public function test_the_registration_desk_needs_its_permission(): void
    {
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->withPermissions(Permission::TicketRequests)->create())
            ->get(route('check-in.index', $event))->assertForbidden();
        $this->actingAs($this->staffWithRole('Registration Desk'))
            ->get(route('check-in.index', $event))->assertOk();
    }

    public function test_the_admin_opens_everything(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.events.content.edit', $event))->assertOk();
        $this->actingAs($admin)->get(route('check-in.index', $event))->assertOk();
    }
}
```

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --compact tests/Feature/Admin/PermissionAccessTest.php`
Expected: FAIL — the Sales, Speakers, Project Manager and Registration Desk tests fail because the old `admin` middleware refuses every non-admin (and `check-in.*` doesn't check permissions yet). The route-coverage test may already pass.

- [ ] **Step 3: Write the middleware**

Replace `app/Http/Middleware/EnsureUserHasPermission.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a staff member through only to pages their role allows. A route no permission lists is
 * treated as Admin-only, so a page added later is closed until someone decides otherwise.
 */
class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();
        $permission = AdminPermissions::for($routeName);

        $allowed = $user !== null && ($permission !== null ? $user->hasPermission($permission) : $user->isAdmin());

        abort_unless($allowed, 403);

        return $next($request);
    }
}
```

Delete `app/Http/Middleware/EnsureUserIsAdmin.php`.

- [ ] **Step 4: Register it**

`bootstrap/app.php` — replace `use App\Http\Middleware\EnsureUserIsAdmin;` with `use App\Http\Middleware\EnsureUserHasPermission;` and `$middleware->alias(['admin' => EnsureUserIsAdmin::class]);` with `$middleware->alias(['permission' => EnsureUserHasPermission::class]);`.

- [ ] **Step 5: Apply it to the routes**

`routes/web.php`:
- Change `Route::middleware('auth')->prefix('check-in')` to `Route::middleware(['auth', 'permission'])->prefix('check-in')`.
- Change the admin group comment and middleware:

```php
    // Each staff member reaches only what their role allows (App\Enums\Permission); anything
    // not mapped to a permission, like Staff and Roles, is for the Admin role alone.
    Route::middleware(['auth', 'permission'])->group(function () {
```

- Directly after the `logout` route, add:

```php
    Route::view('no-access', 'admin.no-access')->middleware('auth')->name('no-access');
```

Create a placeholder `resources/views/admin/no-access.blade.php` (Task 4 fills it in):

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('No access yet')" />
@endsection
```

Confirm nothing else references the removed alias: `grep -rn "'admin'\]\|EnsureUserIsAdmin" app bootstrap routes tests` → no results.

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/PermissionAccessTest.php tests/Feature/Admin/StaffRolesTest.php tests/Feature/Admin/StaffCrudTest.php`
Expected: PASS. If `test_every_admin_route_is_mapped_or_admin_only` lists a route, map it in `Permission::routes()` (it belongs to the section whose screen links to it) — do not add it to `ADMIN_ONLY` unless it is genuinely admin-only.

- [ ] **Step 7: Full suite, format, commit**

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
git add -A app bootstrap routes resources/views/admin/no-access.blade.php tests
git commit -m "feat: guard every admin page by the permission its section needs"
```

---

### Task 4: Sidebar, login landing page and the no-access page

**Files:**
- Modify: `app/Support/AdminPermissions.php` (add `homeFor()`)
- Modify: `app/Http/Controllers/Admin/AuthController.php`
- Modify: `resources/views/admin/partials/sidebar.blade.php`
- Modify: `resources/views/admin/no-access.blade.php`
- Test: `tests/Feature/Admin/PermissionNavigationTest.php` (new); `tests/Feature/Admin/StaffRolesTest.php` (must keep passing)

**Interfaces:**
- Consumes: `Permission::entryRoute()`, `needsEvent()` (Task 1); `User::hasPermission()`, `isAdmin()` (Task 2); route `admin.no-access` (Task 3).
- Produces: `AdminPermissions::homeFor(User $user): string` (absolute URL).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/PermissionNavigationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithRole(string $name, string $email): User
    {
        return User::factory()->create(['email' => $email, 'role_id' => Role::where('name', $name)->sole()->id]);
    }

    private function logIn(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.login'), ['email' => $email, 'password' => 'password']);
    }

    public function test_each_starting_role_lands_on_its_first_section(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $this->staffWithRole('Sales', 'sales@example.com');
        $this->staffWithRole('Project Manager', 'pm@example.com');
        $this->staffWithRole('Registration Desk', 'desk@example.com');
        $this->staffWithRole('Speakers', 'speakers@example.com');

        $this->logIn('sales@example.com')->assertRedirect(route('admin.events.ticket-requests.index', $event));
        $this->post(route('admin.logout'));
        $this->logIn('pm@example.com')->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));
        $this->logIn('desk@example.com')->assertRedirect(route('check-in.events'));
        $this->post(route('admin.logout'));
        $this->logIn('speakers@example.com')->assertRedirect(route('admin.events.speakers.index', $event));
    }

    public function test_the_event_opened_is_the_latest_published_one(): void
    {
        Event::factory()->create(['status' => EventStatus::Published, 'start_date' => '2026-01-10', 'end_date' => '2026-01-11']);
        $latest = Event::factory()->create(['status' => EventStatus::Published, 'start_date' => '2026-06-10', 'end_date' => '2026-06-11']);
        Event::factory()->create(['status' => 'draft', 'start_date' => '2027-01-10', 'end_date' => '2027-01-11']);
        $this->staffWithRole('Sales', 'sales@example.com');

        $this->logIn('sales@example.com')->assertRedirect(route('admin.events.ticket-requests.index', $latest));
    }

    public function test_login_skips_event_sections_when_there_are_no_events(): void
    {
        $this->staffWithRole('Sales', 'sales@example.com');

        $this->logIn('sales@example.com')->assertRedirect(route('admin.no-access'));
    }

    public function test_a_role_without_permissions_lands_on_the_no_access_page(): void
    {
        $role = Role::factory()->create(['name' => 'Nothing yet']);
        User::factory()->create(['email' => 'new@example.com', 'role_id' => $role->id]);

        $this->logIn('new@example.com')->assertRedirect(route('admin.no-access'));
        $this->get(route('admin.no-access'))->assertOk()->assertSee(__('Your role has no access yet. Ask an admin to give it some.'));
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_the_sidebar_shows_only_what_the_role_allows(): void
    {
        $event = Event::factory()->create();
        $sales = $this->staffWithRole('Sales', 'sales@example.com');

        $this->actingAs($sales)->get(route('admin.events.ticket-requests.index', $event))
            ->assertOk()
            ->assertSee(route('admin.events.ticket-requests.index', $event), false)
            ->assertSee(route('admin.events.discount-coupons.index', $event), false)
            ->assertDontSee(route('admin.events.speakers.index', $event), false)
            ->assertDontSee(route('admin.events.edit', $event), false)
            ->assertDontSee(route('admin.events.create'), false)
            ->assertDontSee(route('admin.site-content.index'), false)
            ->assertDontSee(route('admin.staff.index'), false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_the_admin_sidebar_still_has_everything(): void
    {
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.events.edit', $event), false)
            ->assertSee(route('admin.events.speakers.index', $event), false)
            ->assertSee(route('admin.site-content.index'), false)
            ->assertSee(route('admin.staff.index'), false);
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --compact tests/Feature/Admin/PermissionNavigationTest.php`
Expected: FAIL — Sales is redirected to `check-in.events` by the old login logic; sidebar still shows every link.

- [ ] **Step 3: Add `homeFor()`**

In `app/Support/AdminPermissions.php` add `use App\Enums\EventStatus;`, `use App\Models\Event;`, `use App\Models\User;` and the method:

```php
    /**
     * Where someone lands after logging in: the Dashboard when their role has it, otherwise
     * their first allowed section in Permission order. Sections that belong to an event open for
     * the latest published event (or the latest event of any status); with no events at all they
     * are skipped. A role with nothing to open gets the no-access page.
     */
    public static function homeFor(User $user): string
    {
        $event = null;
        $eventLooked = false;

        foreach (Permission::cases() as $permission) {
            if (! $user->hasPermission($permission)) {
                continue;
            }

            if (! $permission->needsEvent()) {
                return route($permission->entryRoute());
            }

            if (! $eventLooked) {
                $event = Event::query()->where('status', EventStatus::Published)->orderByDesc('start_date')->first()
                    ?? Event::query()->orderByDesc('start_date')->first();
                $eventLooked = true;
            }

            if ($event !== null) {
                return route($permission->entryRoute(), $event);
            }
        }

        return route('admin.no-access');
    }
```

- [ ] **Step 4: Use it on login**

`app/Http/Controllers/Admin/AuthController.php` — add `use App\Support\AdminPermissions;` and replace the ternary return with:

```php
        return redirect()->to(AdminPermissions::homeFor($request->user()));
```

- [ ] **Step 5: Filter the sidebar**

In `resources/views/admin/partials/sidebar.blade.php`:

1. Add a `'permission' => \App\Enums\Permission::…` key to every `$eventSections` row: Landing Page Content, Reels, Gallery, Testimonials, FAQs → `LandingPage`; Speakers → `Speakers`; Speaker Requests → `SpeakerRequests`; Partners, Sponsor Tiers → `Sponsors`; Sponsor Requests → `SponsorRequests`; Invitations, Invitation Requests → `Invitations`; Ticket Types, Request Form, Influencer Categories → `TicketSetup`; Ticket Requests → `TicketRequests`; Discount Coupons → `DiscountCoupons`; Report → `EventReports`; Registration Desk → `RegistrationDesk`; Workshops, Agenda → `AgendaWorkshops`; Contact Messages, Newsletter → `Inbox`.
2. After the `$eventSections` array, add:

```php
    $staffUser = auth()->user();
    $visibleEventSections = array_values(array_filter($eventSections, fn (array $section) => $staffUser?->hasPermission($section['permission'])));
    $canManageEvents = (bool) $staffUser?->hasPermission(\App\Enums\Permission::Events);
    $showEventTree = $canManageEvents || $visibleEventSections !== [];
```

3. Delete the whole `@unless(auth()->user()?->isAdmin()) … @else` check-in branch and its closing `@endunless`, so every user gets the same menu, filtered.
4. Wrap the Dashboard link in `@if($staffUser?->hasPermission(\App\Enums\Permission::Dashboard)) … @endif`.
5. Wrap the whole Events block (`<div class="mt-4" x-data="{ open: … event: … }">` … its closing `</div>`) in `@if($showEventTree) … @endif`; inside it wrap the "All events" and "New Event" links in `@if($canManageEvents) … @endif`, wrap the per-event "Event Details" link in `@if($canManageEvents) … @endif`, and change `@foreach($eventSections as $section)` to `@foreach($visibleEventSections as $section)`.
6. In the Creators Hub block: wrap the Platform Report link in `@if($staffUser?->hasPermission(\App\Enums\Permission::PlatformReport))`, the other five links in `@if($staffUser?->hasPermission(\App\Enums\Permission::HubSite))`, and the whole block in `@if($staffUser?->hasPermission(\App\Enums\Permission::PlatformReport) || $staffUser?->hasPermission(\App\Enums\Permission::HubSite))`.
7. Wrap the Staff link in `@if($staffUser?->isAdmin()) … @endif` (Task 5 adds the Roles link inside the same `@if`).

- [ ] **Step 6: Fill in the no-access page**

Replace `resources/views/admin/no-access.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('No access yet')" />

    <div class="adm-card p-6 max-w-xl">
        <p class="text-hub-dark/75 leading-relaxed">{{ __('Your role has no access yet. Ask an admin to give it some.') }}</p>
    </div>
@endsection
```

Translations (both files): `No access yet` → `لا توجد صلاحيات بعد`; `Your role has no access yet. Ask an admin to give it some.` → `دورك لا يملك أي صلاحيات بعد. اطلب من أحد المسؤولين منحه بعضها.`

- [ ] **Step 7: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/PermissionNavigationTest.php tests/Feature/Admin/StaffRolesTest.php tests/Feature/Admin/PermissionAccessTest.php`
Expected: PASS. `StaffRolesTest::test_check_in_staff_do_not_see_admin_links` must still pass (desk staff no longer see Dashboard, All events or Staff).

- [ ] **Step 8: Full suite, format, commit**

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
git add -A app resources lang tests
git commit -m "feat: show each role only its sections and land it on the first one"
```

---

### Task 5: The Roles page and permission matrix

**Files:**
- Create: `app/Http/Controllers/Admin/RoleController.php` (via `php artisan make:controller Admin/RoleController --no-interaction`)
- Create: `app/Http/Requests/Admin/RoleRequest.php` (via `php artisan make:request Admin/RoleRequest --no-interaction`)
- Create: `resources/views/admin/roles/index.blade.php`, `resources/views/admin/roles/form.blade.php`
- Modify: `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php`
- Test: `tests/Feature/Admin/RoleCrudTest.php` (new); `tests/Feature/HubTranslationCoverageTest.php` (add `Permission` to the enum label check)

**Interfaces:**
- Consumes: `Role` (Task 2), `Permission::grouped()`, `label()` (Task 1); `AdminPermissions::ADMIN_ONLY` already covers `admin.roles.*` (Task 1).
- Produces: routes `admin.roles.index|create|store|edit|update|destroy` (param `role`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/RoleCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_the_roles_page_lists_every_role_with_its_staff_count(): void
    {
        User::factory()->count(2)->checkIn()->create();

        $this->actingAs($this->admin)->get(route('admin.roles.index', ['lang' => 'en']))
            ->assertOk()
            ->assertSeeInOrder(['Admin', 'Project Manager', 'Registration Desk', 'Sales', 'Speakers', 'Sponsors'])
            ->assertSee(route('admin.roles.edit', Role::where('name', 'Sales')->sole()), false)
            ->assertDontSee(route('admin.roles.edit', Role::system()), false);
    }

    public function test_the_form_shows_the_permission_matrix(): void
    {
        $this->actingAs($this->admin)->get(route('admin.roles.create', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('value="ticket_requests"', false)
            ->assertSee('value="registration_desk"', false)
            ->assertSee('Tickets and sales');
    }

    public function test_an_admin_creates_a_role_with_permissions(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Media team',
            'permissions' => ['landing_page', 'agenda_workshops'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'Media team')->sole();
        $this->assertTrue($role->grants(Permission::LandingPage));
        $this->assertTrue($role->grants(Permission::AgendaWorkshops));
        $this->assertFalse($role->grants(Permission::TicketRequests));
    }

    public function test_a_role_can_be_saved_with_no_permissions(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'Waiting'])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertSame([], Role::where('name', 'Waiting')->sole()->permissions);
    }

    public function test_an_admin_edits_a_role(): void
    {
        $sales = Role::where('name', 'Sales')->sole();

        $this->actingAs($this->admin)->put(route('admin.roles.update', $sales), [
            'name' => 'Sales team',
            'permissions' => ['ticket_requests', 'inbox'],
        ])->assertRedirect(route('admin.roles.index'));

        $sales->refresh();
        $this->assertSame('Sales team', $sales->name);
        $this->assertEqualsCanonicalizing(['ticket_requests', 'inbox'], $sales->permissions);
    }

    public function test_names_are_required_and_unique(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'Sales'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Sneaky',
            'permissions' => ['ticket_requests', 'staff'],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['name' => 'Sneaky']);
    }

    public function test_an_unused_role_can_be_deleted(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertModelMissing($role);
    }

    public function test_a_role_with_staff_cannot_be_deleted(): void
    {
        $desk = User::factory()->checkIn()->create();

        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $desk->role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($desk->role);
        $this->assertSame('Registration Desk', $desk->fresh()->role->name);
    }

    public function test_the_admin_role_is_locked(): void
    {
        $system = Role::system();

        $this->actingAs($this->admin)->get(route('admin.roles.edit', $system))->assertForbidden();
        $this->actingAs($this->admin)->put(route('admin.roles.update', $system), ['name' => 'Boss', 'permissions' => []])->assertForbidden();
        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $system))->assertForbidden();

        $this->assertSame('Admin', $system->fresh()->name);
    }

    public function test_only_the_admin_reaches_the_roles_page(): void
    {
        $everything = User::factory()->withPermissions(...Permission::cases())->create();

        $this->actingAs($everything)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($everything)->post(route('admin.roles.store'), ['name' => 'Mine', 'permissions' => ['dashboard']])->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'Mine']);
    }

    public function test_the_admin_sidebar_links_to_roles(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.roles.index'), false);
    }
}
```

In `tests/Feature/HubTranslationCoverageTest.php`, add `use App\Enums\Permission;` and add `Permission::class` to the enum array in `test_every_enum_label_has_an_arabic_translation`.

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --compact tests/Feature/Admin/RoleCrudTest.php`
Expected: FAIL — `Route [admin.roles.index] not defined.`

- [ ] **Step 3: Write the request**

Replace `app/Http/Requests/Admin/RoleRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::enum(Permission::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Role name'),
            'permissions.*' => __('Permission'),
        ];
    }

    /**
     * @return array{name: string, permissions: list<string>}
     */
    public function roleAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'permissions' => array_values(array_unique($this->validated('permissions') ?? [])),
        ];
    }
}
```

- [ ] **Step 4: Write the controller**

Replace `app/Http/Controllers/Admin/RoleController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff roles and the permissions each one grants. The built-in Admin role is shown but locked.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role(['permissions' => []])]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        Role::create($request->roleAttributes());

        return redirect()->route('admin.roles.index')->with('success', __('Role created.'));
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_system, 403);

        return view('admin.roles.form', ['role' => $role]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        $role->update($request->roleAttributes());

        return redirect()->route('admin.roles.index')->with('success', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', __('Move this role\'s staff to another role before deleting it.'));
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', __('Role deleted.'));
    }
}
```

Note: `update()` uses the `RoleRequest` type-hint, which validates before `abort_if` runs; the system role's name is unique-ignored so validation passes and the 403 follows. If the test sees a 302 instead of 403 for the system role PUT, move the guard into `RoleRequest::authorize()`: `return ! ($this->route('role')?->is_system ?? false);`.

- [ ] **Step 5: Add the routes**

In `routes/web.php`, add `use App\Http\Controllers\Admin\RoleController;` with the other admin imports, and directly after the `staff` resource route:

```php
        Route::resource('roles', RoleController::class)->except('show');
```

- [ ] **Step 6: Write the views**

Create `resources/views/admin/roles/index.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Roles')">
        <x-admin.button href="{{ route('admin.roles.create') }}">{{ __('New Role') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-[#b42318]" role="alert">{{ session('error') }}</div>
    @endif

    <p class="mb-5 text-sm text-hub-dark/60">{{ __('A role decides which sections its staff can open. Give each person a role on the Staff page.') }}</p>

    <x-admin.table>
        <thead>
            <tr><th>{{ __('Role') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Access') }}</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td class="font-semibold">
                        {{ $role->name }}
                        @if($role->is_system)
                            <span class="ms-2 inline-flex rounded-full bg-hub-lavender px-2.5 py-0.5 text-xs font-bold text-hub-purple">{{ __('Locked') }}</span>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @if($role->is_system)
                            {{ __('Everything, including Staff and Roles') }}
                        @else
                            {{ trans_choice('{0} No sections|{1} :count section|[2,*] :count sections', count($role->permissions ?? []), ['count' => count($role->permissions ?? [])]) }}
                        @endif
                    </td>
                    <td class="text-end">
                        @unless($role->is_system)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endsection
```

Create `resources/views/admin/roles/form.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$role->exists ? __('Edit Role') : __('New Role')" />

    @php $chosen = old('permissions', $role->permissions ?? []); @endphp

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-5xl">
        @csrf
        @if($role->exists) @method('PUT') @endif

        <div class="adm-card p-6 mb-6 max-w-xl">
            <x-admin.field name="name" label="{{ __('Role name') }}" :value="old('name', $role->name)" required />
        </div>

        <h2 class="font-display font-bold text-lg text-hub-purple mb-1">{{ __('What this role can open') }}</h2>
        <p class="text-sm text-hub-dark/60 mb-4">{{ __('Each tick lets the role view and act in that section, for every event. Staff and Roles stay with admins.') }}</p>

        @if($errors->has('permissions') || $errors->has('permissions.*'))
            <p class="text-sm mb-4 text-[#b42318]">{{ $errors->first('permissions') ?: collect($errors->get('permissions.*'))->flatten()->first() }}</p>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            @foreach(\App\Enums\Permission::grouped() as $group => $permissions)
                <fieldset class="adm-card p-5">
                    <legend class="sr-only">{{ $group }}</legend>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="font-display font-bold text-hub-purple" aria-hidden="true">{{ $group }}</h3>
                        <button type="button"
                            class="text-xs font-bold text-hub-purple hover:underline"
                            x-data
                            @click="const boxes = [...$el.closest('fieldset').querySelectorAll('input[type=checkbox]')]; const all = boxes.every((box) => box.checked); boxes.forEach((box) => { box.checked = ! all })">
                            {{ __('Select all') }}
                        </button>
                    </div>
                    @foreach($permissions as $permission)
                        <label class="flex items-center gap-2.5 py-1.5 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" @checked(in_array($permission->value, $chosen, true)) class="w-4 h-4 rounded border-hub-purple/30 text-hub-purple focus:ring-hub-purple/40">
                            {{ $permission->label() }}
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
        </div>

        <x-admin.button type="submit" class="mt-6">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
```

- [ ] **Step 7: Sidebar link**

In `resources/views/admin/partials/sidebar.blade.php`, inside the `@if($staffUser?->isAdmin())` added in Task 4, after the Staff link:

```blade
    <a href="{{ route('admin.roles.index') }}" class="adm-nav-link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}">
        {{ __('Roles') }}
    </a>
```

- [ ] **Step 8: Translations**

Add to both files (skip existing keys):

| Key | Arabic |
|---|---|
| Roles | الأدوار |
| New Role | دور جديد |
| Edit Role | تعديل الدور |
| Role name | اسم الدور |
| Permission | الصلاحية |
| Access | الصلاحيات |
| Locked | مقفل |
| Everything, including Staff and Roles | كل شيء، بما في ذلك الفريق والأدوار |
| {0} No sections\|{1} :count section\|[2,*] :count sections | {0} لا توجد أقسام\|{1} قسم واحد\|[2,*] :count أقسام |
| What this role can open | ما يمكن لهذا الدور فتحه |
| Each tick lets the role view and act in that section, for every event. Staff and Roles stay with admins. | كل علامة تسمح للدور بعرض ذلك القسم والعمل فيه، لكل الفعاليات. يبقى الفريق والأدوار للمسؤولين فقط. |
| Select all | تحديد الكل |
| Role created. | تم إنشاء الدور. |
| Role updated. | تم تحديث الدور. |
| Role deleted. | تم حذف الدور. |
| Move this role's staff to another role before deleting it. | انقل أعضاء هذا الدور إلى دور آخر قبل حذفه. |
| A role decides which sections its staff can open. Give each person a role on the Staff page. | يحدد الدور الأقسام التي يمكن لأعضائه فتحها. اختر لكل شخص دوره من صفحة الفريق. |

- [ ] **Step 9: Run the tests**

Run: `php artisan test --compact tests/Feature/Admin/RoleCrudTest.php tests/Feature/HubTranslationCoverageTest.php tests/Feature/Admin/PermissionAccessTest.php`
Expected: PASS.

- [ ] **Step 10: Full suite, format, commit**

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
git add -A app resources routes lang tests
git commit -m "feat: add a Roles page where the admin builds roles from a permission matrix"
```

---

### Task 6: Dev database, build and a browser check

**Files:** none changed unless a check fails.

- [ ] **Step 1: Migrate the dev MySQL database**

Run: `php artisan migrate --force`
Expected: the `create_roles_table` migration runs. Then check: `php artisan tinker --execute 'echo App\Models\Role::withCount("users")->get()->map(fn ($r) => $r->name.":".$r->users_count)->implode(", ");'` — expected `Admin:1` (or the number of existing admins) plus the five starting roles.

- [ ] **Step 2: Build assets**

Run: `npm run build` — expected `built in …` with no errors.

- [ ] **Step 3: Browser check**

With the dev server on `http://127.0.0.1:8000`, using Playwright from the scratchpad (see the earlier `shots.js`), in Arabic:
1. Log in as the admin; open `/admin/roles`; screenshot the list and the edit form of "Sales"; click a group's "Select all" and confirm every box in that card toggles.
2. As admin, create a staff member with the Sales role (Staff page); log out; log in as them; confirm the landing page is Ticket Requests for the latest event and the sidebar shows only Ticket Requests, Invitations, Invitation Requests, Discount Coupons and Report under each event; screenshot the sidebar.
3. Open `/admin/staff` as that user — expect the 403 page.
4. Log back in as admin and delete the test staff member.
Report any page errors from the console.

- [ ] **Step 4: Final full suite**

Run: `php artisan test --compact` — expected all green. Report the count.

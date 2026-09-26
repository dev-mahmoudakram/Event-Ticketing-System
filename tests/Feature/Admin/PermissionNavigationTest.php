<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PermissionNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithRole(string $name, string $email): User
    {
        return User::factory()->create(['email' => $email, 'role_id' => Role::where('name', $name)->sole()->id]);
    }

    private function logIn(string $email): TestResponse
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

    public function test_desk_staff_keep_their_one_click_desk_list(): void
    {
        $event = Event::factory()->create();
        $desk = $this->staffWithRole('Registration Desk', 'desk@example.com');

        // The desk page itself has no link back to its event, so any link here is the sidebar's.
        $page = $this->actingAs($desk)->get(route('check-in.index', $event))->assertOk();

        $this->assertSame(1, substr_count($page->getContent(), 'href="'.route('check-in.index', $event).'"'));
        $page->assertDontSee(route('admin.events.index'), false);
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

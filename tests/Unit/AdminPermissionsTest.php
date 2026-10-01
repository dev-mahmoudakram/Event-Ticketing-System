<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Permission;
use App\Support\AdminPermissions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

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
            'session types' => ['admin.events.session-types.reorder', Permission::AgendaWorkshops],
            'locations' => ['admin.events.locations.edit', Permission::AgendaWorkshops],
            'pages' => ['admin.events.pages.edit', Permission::Pages],
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

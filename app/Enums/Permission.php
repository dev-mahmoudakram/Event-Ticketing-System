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

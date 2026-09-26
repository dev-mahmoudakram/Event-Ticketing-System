# Admin roles and permissions — design

**Date:** 2026-09-26
**Status:** Approved in conversation, awaiting written-spec review

## Goal

Let the platform admin create staff roles and choose what each role can do, through a
permission matrix in the admin panel. Ship with five starting roles for the event team
(Speakers, Sponsors, Sales, Project Manager, Registration Desk) that the admin can edit,
rename or delete.

## Decisions (from the conversation)

- A separate built-in **Admin** role sits on top with full access. It cannot be edited or
  deleted, and it is the only role that manages staff and roles.
- Roles live in the database; **permissions are a fixed list in code**, because each
  permission guards real routes. The admin combines permissions into roles but cannot invent
  new ones.
- **One permission per admin section** — it grants viewing and acting in that section.
  No separate view-only / manage levels.
- A role applies to **all events**; there is no per-event scoping.
- Each staff member has exactly **one role**.
- No new dependencies (no `spatie/laravel-permission`).

## Data model

### `roles` table (new)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `name` | string, unique | What the admin calls the role, e.g. "Sales" |
| `permissions` | json | List of `Permission` values, e.g. `["ticket_requests","invitations"]` |
| `is_system` | boolean, default false | True only for Admin: locked, holds every permission |
| timestamps | | |

### `users` table

- Add `role_id` — foreign key to `roles`, `restrictOnDelete()` (a role with staff cannot be
  deleted at the database level either).
- Drop the `role` string column once data is moved.
- `App\Enums\UserRole` is deleted.

### Migration and data move

One migration, safe to run on the live server with `php artisan migrate --force`:

1. Create `roles`.
2. Insert the six starting roles (below).
3. Add `users.role_id`, then set it: `role = 'admin'` → Admin, `role = 'check_in'` →
   Registration Desk, anything else → Registration Desk (least access).
4. Make `role_id` required and drop `users.role`.

The `down()` restores `users.role` from each user's role (`is_system` → `admin`, otherwise
`check_in`) and drops `role_id` and `roles`.

## Permissions

`App\Enums\Permission` (string-backed), each with a translated `label()` and a `group()` for
the matrix layout. Route names are matched against each permission's patterns in one place:
`App\Support\AdminPermissions`.

| Group | Permission (value) | Routes |
|---|---|---|
| Overview | Dashboard (`dashboard`) | `admin.dashboard` |
| Event setup | Events (`events`) | `admin.events.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` |
| | Landing page (`landing_page`) | `admin.events.content.*`, `admin.events.reels.*`, `admin.events.gallery-photos.*`, `admin.events.testimonials.*`, `admin.events.faqs.*` |
| | Agenda and workshops (`agenda_workshops`) | `admin.events.agenda-items.*`, `admin.events.workshops.*` |
| Speakers and sponsors | Speakers (`speakers`) | `admin.events.speakers.*` |
| | Speaker requests (`speaker_requests`) | `admin.events.speaker-requests.*` |
| | Sponsors (`sponsors`) | `admin.events.sponsors.*`, `admin.events.sponsor-tiers.*` |
| | Sponsor requests (`sponsor_requests`) | `admin.events.sponsor-requests.*` |
| Tickets and sales | Ticket setup (`ticket_setup`) | `admin.events.ticket-types.*`, `admin.events.request-form-fields.*`, `admin.events.influencer-categories.*` |
| | Ticket requests (`ticket_requests`) | `admin.events.ticket-requests.*` |
| | Invitations (`invitations`) | `admin.events.invitations.*`, `admin.events.invitation-requests.*` |
| | Discount coupons (`discount_coupons`) | `admin.events.discount-coupons.*` |
| | Event reports (`event_reports`) | `admin.events.reports.*` |
| | Inbox (`inbox`) | `admin.events.contact-messages.*`, `admin.events.newsletter-subscribers.*` |
| Event day | Registration desk (`registration_desk`) | `check-in.*` |
| Creators Hub | Hub site (`hub_site`) | `admin.site-content.*`, `admin.site-faqs.*`, `admin.hero-slides.*`, `admin.hub-partners.*`, `admin.audience-tabs.*` |
| | Platform report (`platform_report`) | `admin.reports.*` |

**Admin only, never grantable:** `admin.staff.*`, `admin.roles.*`, and **any route not listed
above** (deny by default).

Note: the event CRUD routes are listed by exact name, never as `admin.events.*`, because every
per-event section also starts with `admin.events.`.

## Starting roles

| Permission | Speakers | Sponsors | Sales | Project Manager | Registration Desk |
|---|:-:|:-:|:-:|:-:|:-:|
| Dashboard | | | | ✅ | |
| Speakers | ✅ | | | ✅ | |
| Speaker requests | ✅ | | | ✅ | |
| Sponsors | | ✅ | | ✅ | |
| Sponsor requests | | ✅ | | ✅ | |
| Ticket requests | | | ✅ | ✅ | |
| Invitations | | | ✅ | ✅ | |
| Discount coupons | | | ✅ | ✅ | |
| Event reports | | | ✅ | ✅ | |
| Registration desk | | | | | ✅ |

Plus **Admin** (`is_system`), which has everything.

## Enforcement

- `User::hasPermission(Permission $permission): bool` — true for a system-role user, otherwise
  whether the role's list contains it. `User::isAdmin()` stays and means "has the system role".
- New middleware **`permission`** (`App\Http\Middleware\EnsureUserHasPermission`) replaces the
  `admin` middleware on the admin route group and is added to the `check-in` group. It resolves
  the current route name through `AdminPermissions::for(string $routeName): ?Permission`:
  - a permission → allow if the user has it, else 403;
  - null (unmapped, or Staff/Roles) → allow only admins, else 403.
- Login, logout and the login form stay outside the middleware.
- `EnsureUserIsAdmin` and the `admin` alias are removed.

## Screens

### Sidebar

Built from the same permission check: each link shows only if the user may open it. The event
tree shows when the user has at least one per-event permission; within each event, only the
allowed sections are listed. The Registration Desk list replaces the current special-case
branch for check-in staff.

### After login

The admin goes to the Dashboard. Anyone else goes to the Dashboard if they have it, otherwise to
their first allowed section in the table order above; per-event sections open for the most
recently started published event (falling back to the most recent event). A user whose role has
no permissions sees a short "Your role has no access yet — ask an admin" page instead of a 403.

### Roles page (new, Admin only) — `admin.roles.*`

- **Index:** name, number of staff, number of permissions; Admin shown first with a lock badge
  and no edit/delete.
- **Create / edit:** name field and the permission matrix — one card per group, a checkbox per
  permission with its label, and a "select all" toggle per group.
- **Delete:** refused with a message while staff still have the role (the form's confirm dialog
  asks first).
- Sidebar link "Roles" next to "Staff".

### Staff page (changed)

- The role picker lists roles from the table (Admin first).
- Kept guards: the last admin cannot be removed or demoted; you cannot demote or delete yourself.
- Index shows the role name.

## Validation and guards

- Role name: required, max 60, unique.
- Permissions: an array; each value must be a `Permission` case (unknown values rejected).
- System role: editing and deleting return 403; its permissions are never read from the column.
- Role in use: delete refused; the database `restrictOnDelete` backs this up.

## Testing

- **Route coverage:** walk every registered route named `admin.*` or `check-in.*` (except
  login/logout) and assert it maps to a permission or is on the explicit admin-only list —
  so a new page can't be added without a decision.
- **Access per starting role:** for each role, an allowed page returns 200 and a forbidden page
  returns 403 (including the Staff and Roles pages for every non-admin role).
- **Deny by default:** a non-admin with every permission still gets 403 on Staff and Roles.
- **Migration:** users with `admin` / `check_in` land on Admin / Registration Desk.
- **Sidebar:** a Sales user sees Ticket Requests and not Speakers; an admin sees everything.
- **Login redirect:** Registration Desk → the desk; Sales → Ticket Requests; Project Manager → Dashboard.
- **Roles CRUD:** create with permissions, edit, delete-when-unused, delete refused when in use,
  system role locked, unknown permission rejected.
- **Staff:** assign a role; last-admin and self guards still hold.
- **Translations:** every permission label and group has Arabic.

## Out of scope

- Per-event role scoping.
- View-only vs manage levels.
- Several roles per user, or per-user permission overrides.
- An audit log of who changed roles.

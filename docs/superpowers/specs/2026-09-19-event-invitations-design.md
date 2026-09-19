# Event Invitations — Design

## Goal

Let an admin invite specific people (typically influencers/sponsors) to
request a ticket without going through the public, payment-based flow. The
admin generates a single-use link + OTP, sends it to the invitee through
whatever channel they choose, the invitee verifies the OTP and fills a
fixed request form, and the admin approves or rejects — approval issues a
real ticket by email immediately, with no payment step.

## Out of scope

- Reusable/multi-use invitations (each invitation admits exactly one person).
- Per-event configurable form fields for invitations (the field set is fixed).
- Any payment step for invitation-issued tickets.
- Editing/regenerating an invitation in place (an admin revokes and creates
  a new one instead).

## Data model

### `invitations` table

| column | type | notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK → events | |
| `ticket_type_id` | FK → ticket_types | the type this invite grants once approved |
| `token` | string, unique | opaque `Str::random(40)`, appears in the public URL |
| `otp` | string(6) | plain 6-digit code; admin can view it anytime, so it is not hashed |
| `status` | enum: `Unused`, `Used`, `Revoked` | |
| `expires_at` | datetime | `created_at + 7 days`, set at creation |
| `created_at` / `updated_at` | | |

Expiry is derived by comparing `expires_at` to `now()`, not stored as a
fourth status value — this avoids a stored value that could drift out of
sync with the clock.

### `invitation_requests` table

| column | type | notes |
|---|---|---|
| `id` | bigint | |
| `invitation_id` | FK → invitations, **unique** | enforces single-use at the DB level |
| `name` | string | |
| `email` | string | |
| `phone` | string | |
| `influencer_category_id` | nullable FK → influencer_categories | |
| `influencer_category_other` | nullable string | mirrors the ticket-request form's "Other" free-text |
| `instagram_url` | nullable string | |
| `instagram_followers` | nullable unsigned int | |
| `facebook_url` | nullable string | |
| `facebook_followers` | nullable unsigned int | |
| `tiktok_url` | nullable string | |
| `tiktok_followers` | nullable unsigned int | |
| `status` | enum: `Pending`, `Approved`, `Rejected` | |
| `ticket_id` | nullable FK → tickets | set once approved |
| `created_at` / `updated_at` | | |

Both tables belong to `Event` (`invitations()`/`invitationRequests()`
`hasMany`), matching how `TicketRequestField`, `InfluencerCategory`, etc.
are scoped in this codebase.

## Admin flow

### Generating an invitation

`admin/events/{event}/invitations` (index):

- "Generate Invitation" opens a small form: pick one of the event's active
  ticket types.
- On submit, creates the `Invitation` row (`token` = `Str::random(40)`,
  `otp` = a random 6-digit string, `expires_at` = `now()->addDays(7)`,
  `status` = `Unused`), then shows a one-time confirmation panel with the
  full invite URL and the OTP, both copyable.
- The list below shows every invitation for the event: ticket type,
  status (Unused / Used / Revoked / Expired — Expired computed from
  `expires_at`), the OTP (always visible/copyable, per product decision),
  and a "Revoke" action for any `Unused` row (sets `status = Revoked`).
  Revoking an already-`Used` or `Revoked` invitation is a no-op guarded in
  the controller.

### Reviewing requests

`admin/events/{event}/invitation-requests` (index + `updateStatus`),
modeled directly on `Admin\TicketRequestQueueController`:

- `index()` filters by `?status=` (default `Pending`), shows each
  request's fields and the invitation it came from.
- `updateStatus(Event, InvitationRequest, status)`:
  - **Approve**: in a DB transaction, create a `Ticket`
    (`ticket_type_id` from the invitation, `price` = that ticket type's
    price, `status` = `TicketStatus::TicketIssued`, `is_paid` = true,
    `payment_method` = `'invitation'`, plus the request's name/email/phone/
    influencer_category_id/influencer_category_other), call
    `WorkshopBooker::issueKeyFor()`, generate
    the QR via `TicketQrCode::pngFor()`, send the **existing**
    `TicketIssued` mail unchanged, then set
    `invitation_requests.ticket_id` and `status = Approved`.
  - **Reject**: send a new `InvitationRequestRejected` mail, set
    `status = Rejected`. No ticket is created.
  - Same safety pattern as the existing controller: send mail first: only
    persist the status change if the send succeeds; otherwise flash an
    error and leave the row untouched.
- The social answers (Instagram/Facebook/TikTok URL + followers) are shown
  read-only in the request detail — no new dynamic-field machinery, since
  they're fixed columns on `invitation_requests`.

## Public flow

1. `GET /events/{event}/invite/{token}` — looks up the invitation by
   `token`. If missing, revoked, expired, or already used, shows a single
   static "This invitation is no longer valid" page (localized) regardless
   of which of those reasons applies — the visitor is never told which.
   Otherwise shows the OTP entry screen.
2. `POST` the OTP to the same route — compares with `hash_equals` against
   the stored `otp`. Rate-limited (Laravel's `throttle` middleware, 5
   attempts/minute keyed by IP + invitation token) to resist brute-forcing
   a 6-digit code. On success, stores a session flag
   (`invitation_verified.{invitation_id} = true`) and redirects to the
   request form. On failure, re-shows the OTP screen with an error.
3. `GET /events/{event}/invite/{token}/request` — only reachable when the
   session flag from step 2 is set for this invitation (otherwise redirect
   back to step 1); renders the fixed form:
   - Name, Email, Phone, Influencer Category (dropdown; required/optional
     follows the event's existing `require_influencer_category` setting,
     including the "Other" option and free-text reveal already built for
     the ticket-request form)
   - Instagram profile URL + Followers (same row)
   - Facebook profile URL + Followers (same row)
   - TikTok profile URL + Followers (same row)
   - All three social rows are always shown (fixed form, no per-field
     admin configuration), matching the ticket-request form's now-always-on
     follower count.
4. `POST` the form — validates, creates the `InvitationRequest`
   (`status = Pending`), sets the `Invitation`'s `status = Used`, sends a
   "request received" confirmation email (new `InvitationRequestReceived`
   mailable, modeled on `TicketRequestReceived`), clears the session flag,
   and shows a confirmation page. Re-visiting the link afterward hits the
   "no longer valid" page from step 1 (status is now `Used`).

## Mail

Two new mailables, matching the existing `Mailable` pattern
(`Queueable`, `SerializesModels`, one Blade view each):

- `InvitationRequestReceived` — sent on submission; confirms receipt.
- `InvitationRequestRejected` — sent on admin rejection.

No new "approved" mailable — approval reuses `TicketIssued` unchanged,
since the resulting `Ticket` is issued exactly the same way a paid
ticket is.

## Error handling

- An invitation token that doesn't resolve to a row is treated identically
  to an expired/used/revoked one (same static page) — this also avoids
  leaking whether a given token ever existed.
- OTP verification is rate-limited per the above; exceeding the limit
  shows Laravel's standard "too many attempts" response.
- Approve/reject email failures block the status change (existing pattern)
  so the queue never silently diverges from what was actually communicated
  to the invitee.

## Testing

- Feature tests for: generating an invitation (admin), revoking, viewing
  the invite link when unused/expired/used/revoked, OTP success/failure/
  rate-limit, submitting the request form (including "Other" category and
  all three social rows), approve → ticket created with QR + workshop key
  + `TicketIssued` mail sent, reject → `InvitationRequestRejected` sent
  and no ticket created, single-use enforcement (second submission attempt
  against a `Used` invitation is rejected), cross-event guards
  (`assertBelongsToEvent`-style checks) on both admin controllers.

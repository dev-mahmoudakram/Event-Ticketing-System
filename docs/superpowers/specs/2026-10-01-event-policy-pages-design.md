# Event policy pages — design

**Date:** 2026-10-01
**Status:** Approved in conversation, awaiting written-spec review

## Goal

Give every event the public pages an Egyptian payment gateway (Kashier, Geidea, Fawry) checks
before approving a merchant — Terms & Conditions, Privacy Policy, Refund & Cancellation Policy,
Delivery Policy, About and Contact — fully editable per event from the admin dashboard, in
Arabic and English, and linked where a reviewer and a buyer expect them.

## Decisions (from the conversation)

- Gateway: Kashier / Geidea / Fawry. Their review looks for public Terms, Privacy, Refund &
  Cancellation and Delivery/Service pages, a Contact page with phone, email and address, About
  information naming the business, prices shown in EGP, and card logos.
- **Flexible pages with a required set:** each event has a Pages section; the admin can add any
  page. Six required pages are created automatically with starter drafts and can't be deleted.
- Pages belong to an **event** (e.g. CCS), not to the Creators Hub root site.
- Starter drafts are a starting point, not legal advice; the admin must review them, and pages
  with unfilled placeholders are flagged.

## Data model

### `event_pages` (new)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK → events, cascade on delete | |
| `key` | string(30), nullable | `terms`, `privacy`, `refund`, `delivery`, `about`, `contact` for the required pages; null for custom pages |
| `slug` | string(100) | unique per event (`unique(['event_id', 'slug'])`); lowercase letters, digits, single dashes |
| `title_ar`, `title_en` | string(150) | |
| `body_ar`, `body_en` | longText, nullable | `SanitizedRichText` cast |
| `show_in_footer` | boolean, default true | |
| `is_published` | boolean, default true | |
| `sort_order` | unsigned int, default 0 | |
| timestamps | | `updated_at` is shown as "Last updated" |
| | | unique(`event_id`, `key`) |

### Required pages

| Key | Slug | Title (en / ar) |
|---|---|---|
| `terms` | `terms` | Terms & Conditions / الشروط والأحكام |
| `privacy` | `privacy-policy` | Privacy Policy / سياسة الخصوصية |
| `refund` | `refund-policy` | Refund & Cancellation Policy / سياسة الاسترداد والإلغاء |
| `delivery` | `delivery-policy` | Ticket Delivery Policy / سياسة تسليم التذاكر |
| `about` | `about` | About Us / من نحن |
| `contact` | `contact` | Contact Us / تواصل معنا |

- Defined once in code (`App\Enums\RequiredPage` — key, slug, titles, sort order, draft view).
- Created for every existing event by a migration, and for each new event by the same
  `Event::created` hook that seeds session types (`EventPage::seedRequiredFor(Event)`), which the
  CCS seeder also calls when model events are off.
- Rules for a required page: can't be deleted, its `key` and `slug` can't change, and it can't be
  unpublished (the toggle is hidden and the request ignores it). Title, body and
  `show_in_footer` stay editable.

### Starter drafts

- Blade templates under `resources/views/event-pages/drafts/{key}-{locale}.blade.php`, rendered
  once at creation with the event: name, contact email, contact phone, venue name and address,
  and the currency of its ticket types (EGP).
- Anything only the business knows is a bracketed placeholder: **[Company legal name]**,
  **[Commercial registration number]**, **[Tax ID]**, **[Business address]**,
  **[Refund window, e.g. 7 days before the event]**. Arabic drafts use Arabic placeholders in the
  same `[…]` form.
- Draft content outline (both languages):
  - **Terms:** who runs the event (legal name), what a ticket grants, the request → review →
    approval → payment → e-ticket flow, one ticket per attendee, admission with the QR code,
    workshop booking rules, code of conduct, changes to the programme, liability, governing law
    (Egypt), contact.
  - **Privacy:** what is collected (name, email, phone, social links, follower counts, uploaded
    files), why (reviewing requests, issuing tickets, check-in, event updates), payment data
    handled by the gateway and never stored by the site, retention, sharing (none for marketing),
    attendee rights (access, correction, deletion via contact email), cookies (session and
    language only), contact.
  - **Refund & Cancellation:** pending requests can be withdrawn at no charge; paid tickets
    refundable until the [refund window]; refunds to the original card via the gateway within
    [X business days]; non-refundable after the window or once checked in; full refund if the
    event is cancelled; date or venue change rules; how to request (contact email + reference).
  - **Delivery:** tickets are digital only — after payment the e-ticket (QR code + workshop
    booking key) is emailed to the address on the request immediately; no physical shipping; what
    to do if it doesn't arrive (check spam, contact with the reference); the ticket page link.
  - **About:** the organiser (legal name, registration), what the event is, dates and venue,
    contact.
  - **Contact:** short intro text; the page adds the details block and form (see below).

`EventPage::needsDetails(): bool` is true while either language's body still contains a
`[…]` placeholder (regex `\[[^\]\n]{2,80}\]`). Ordinary markdown-like brackets aren't used in the
editor's HTML, so false positives are unlikely; the flag is advisory only.

## Admin

- Permission: new `Permission::Pages` (group "Event setup", label "Pages"), routes
  `admin.events.pages.*`. Project Manager doesn't get it by default; Admin always has it.
- Sidebar: "Pages" under each event, after Landing Page Content.
- **Index:** title, address (`/events/{slug}/pages/{page}`), badges — Required, Draft
  (unpublished), **Needs your details** — footer on/off, Edit / View page / Delete (delete hidden
  for required pages); drag to reorder (existing sortable pattern, `reorder` route).
- **Form:** title (ar/en), slug (read-only for required pages), body (ar/en, rich text), "Show in
  footer", "Published" (hidden for required pages). A note above the body on drafts: "This is a
  starter draft. Replace every [placeholder] and have it reviewed before going live."
- Validation: titles required (max 150); slug required, `regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/`,
  max 100, unique per event (no reserved words are needed, since pages live under `/pages/`);
  body nullable string.

## Public

- Route: `GET /events/{event}/pages/{slug}` → `event-pages.show`, inside the existing
  `EnsureEventIsPublished` group. 404 for an unknown slug or an unpublished page.
- Layout: the event layout (nav + footer, Arabic/English), page title, "Last updated: {date}",
  the body (sanitized HTML), styled with the existing `.ccs-richtext` class.
- Contact page: below its body, the event's contact email (mailto), phone (tel), venue name and
  address, then the existing contact form (reuse the landing page's contact form partial so
  submissions keep going to Contact Messages with the same rate limit).
- Footer: a "Policies" column listing published pages with `show_in_footer`, by `sort_order`.
- Card logos: a small row of Visa, Mastercard and Meeza marks (inline SVG component
  `x-payment-logos`, no new dependency) in the event footer when the event has an active ticket
  type with a price above zero.

## Agreement

- **Ticket request form:** a required checkbox `accept_terms` — "I have read and agree to the
  Terms & Conditions and the Refund & Cancellation Policy", both linked (new tab). Validation
  rule `accepted`. The ticket gets `terms_accepted_at` (nullable timestamp, new column) set at
  submission.
- **Approval email** (the one carrying the payment link): a line "By paying you agree to the
  Terms & Conditions and the Refund & Cancellation Policy" with both links, in the reader's
  language. (There is no separate checkout page yet; the real gateway work will add one and
  carry the same line.)
- Invitation-request and free flows are unchanged.

## Testing

- Migration and the `Event::created` hook give every event the six required pages, once each;
  re-running the seeder doesn't duplicate them.
- Drafts include the event's name, email, phone and currency, and are flagged "Needs your
  details" until placeholders are gone.
- Admin: create/edit/reorder/delete custom pages; slug format and per-event uniqueness;
  required pages can't be deleted, can't change slug, can't be unpublished; another event's page
  is 404 in the admin.
- Public: page renders in each language with "Last updated"; unpublished/unknown → 404; draft
  event → 404 (existing middleware); Contact page shows the details and form.
- Footer lists only published footer pages in order; card logos only when paid tickets exist.
- Ticket request without `accept_terms` → validation error, nothing saved; with it →
  `terms_accepted_at` set.
- Approval email contains both policy links.
- Permission map: new routes resolve to `Permission::Pages`; translation coverage passes.
- Browser check: admin Pages list and form; public pages in Arabic and English; footer column
  and logos; ticket form checkbox.

## Out of scope

- Pages for the Creators Hub root site (can reuse the same model later with a nullable event).
- Version history of page edits.
- Automatic legal text per gateway; the drafts are generic and must be reviewed.
- A checkout page (comes with the real payment gateway).

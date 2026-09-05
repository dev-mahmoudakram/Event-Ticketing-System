# Project Status

Consolidated checklist across all 10 build phases (see the individual phase files in this
directory for scope). Reflects actual code in the repo, not intent — re-verify against routes/
models before trusting this after major work lands, since it goes stale the moment new code
ships without an update here.

Last verified: 2026-09-05 (routes, models, controllers, migrations inspected directly).

## At a glance

| # | Phase | Status |
|---|-------|--------|
| 01 | Project Setup | ✅ Done |
| 02 | Database Design | ✅ Done — every table the ten phases need now exists |
| 03 | Admin Panel | ✅ Done — content CRUD, approvals, coupons and reports |
| 04 | Landing Page | ✅ Done — both brands (CCS event pages and the Creators Hub platform page) |
| 05 | Ticket Request | ✅ Done — request, review, approve/reject, approval email with a payment link |
| 06 | Payment | 🟡 Partial — the full flow works on a stub link; no real gateway |
| 07 | Workshops | ✅ Done — browsing, keyed booking, capacity and slot enforcement |
| 08 | Awards | 🟡 Partial — teaser and page shell built, voting missing |
| 09 | QR System | ✅ Done — QR on issuance, signed scan URL, staff check-in portal |
| 10 | Reports | ✅ Done — per-event report screen and CSV export |

Eight phases complete, two partial (payment gateway, awards voting).

## 01 — Project Setup

- [x] Laravel 12 + PHP 8.3, MySQL, Laravel Boost
- [x] Base Blade layout with RTL/LTR (Arabic + English) support
- [x] Bilingual UI string catalog (`lang/ar.json`, `lang/en.json`) covering the whole app
- [x] A test guards that no landing page default ships without an Arabic translation

Note: the phase doc originally scoped "Bootstrap 5 + SCSS" — the project uses Tailwind CSS +
AlpineJS instead (see `.claude/CLAUDE.md`). Doc updated to match reality.

## 02 — Database Design

- [x] Events (including cover image, logo, footer logo, favicon, Apple touch icon, share image,
      contact email/phone and social links), Speakers, Sponsors, TicketTypes (+ features),
      Workshops, AgendaItems, Faqs, LandingPageContent, GalleryPhotos, Testimonials,
      ContactMessages, NewsletterSubscribers, Reels
- [x] Platform-side content: SiteContent (registry-driven CMS), SiteFaq, HeroSlide, HubPartner
- [x] Ticket (attendee ticket + workflow state), `TicketRequestField`, `TicketRequestAnswer`
- [x] `workshop_bookings` — which ticket holds which place, unique per ticket per workshop;
      capacity and slot allowance are counted from these rows rather than stored as tallies
- [x] `discount_coupons` (+ `tickets.discount_coupon_id`, `price`, `discount_amount`) — the
      price is copied onto the ticket at request time, so nothing rewrites it afterwards
- [x] `awards` and `award_votes` (+ the voting window on `events`) — one confirmed vote per
      email per category, unconfirmed rows are pending rather than cast

The `tickets.workshop_booking_key` column still has nothing writing to it — it is issued as part
of the Workshops booking flow (Phase 07).

## 03 — Admin Panel

- [x] Admin auth (login/logout), rebuilt in the Creators Hub identity
- [x] Grouped menu: Events expands to every event, each event onto its own sections
- [x] Events CRUD, including per-event branding, contact details and social links
- [x] Ticket Types, Workshops, Speakers, Sponsors, Agenda Items, FAQs, Gallery Photos,
      Testimonials, Reels CRUD
- [x] Landing Page Content CMS per event, with per-section show/hide and an About image
- [x] Creators Hub CMS: content registry, hero slides, partners, FAQs, logos, sharing, contact
- [x] Contact Messages and Newsletter Subscribers (read-only indexes)
- [x] Ticket Request Form field builder; request review/approve/reject queue with emails
- [x] Discount Coupons admin (per event, percentage or fixed, usage limit, validity window)
- [x] Per-event Report screen: funnel, tickets by status, revenue by type, arrivals by hour,
      coupon use, and a CSV export of the attendee list

## 04 — Landing Page

- [x] CCS event page (hero, about, speakers, workshops teaser, tickets, awards teaser, gallery,
      testimonials, partners, FAQ, location, contact, newsletter/footer), reels, GSAP motion
- [x] Creators Hub platform page at `/` (hero slider, stats, about, audiences deck, events,
      community, partners, FAQ, contact, footer), every string and image CMS-editable
- [x] Standalone Agenda page, events index
- [x] Bilingual throughout, admin-controlled section visibility, scroll-reveal motion
- [x] Favicons, web manifest, Open Graph and Twitter cards, per-event overrides

## 05 — Ticket Request

- [x] Public request form, pre-selects a ticket type, no login
- [x] Admin-configurable extra fields (Instagram, Portfolio URL/PDF, CV upload) with private
      file storage
- [x] Submission endpoint creating a Pending ticket, with an animated confirmation popup
- [x] Admin review screen with Approve / Reject
- [x] Approve emails a signed payment link (7 days); Reject emails a decline

## 06 — Payment

- [x] Payment link flow (Payment Pending → Paid → Ticket Issued) behind a signed URL
- [x] QR code and issued-ticket email on payment success
- [x] The price and any discount are recorded on the ticket at request time
- [ ] Real gateway (Kashier — waiting on their approval); `TicketPaymentController` marks a
      ticket paid without taking money
- [ ] Payment records: gateway reference, refunds
- [x] Workshop Booking Key generated on payment success

## 07 — Workshops

- [x] Admin CRUD for workshops
- [x] Public workshop browsing (`workshops.index`, `workshops.show`)
- [x] Landing page workshops teaser (capacity shown, no fabricated fill %)
- [x] `WorkshopBooking` model, capacity and slot-allowance helpers
- [x] Reference number + Workshop Booking Key opens a picker, with no login
- [x] The key is issued with the ticket, and only for a tier that includes workshops
- [x] Each ticket type's `workshop_slot_count` is enforced (blank = unlimited, 0 = no picker)
- [x] Capacity is enforced under a row lock, so two people cannot take the last place
- [x] Admin sees who booked each workshop; the report shows how full each one is

## 08 — Awards

- [x] Admin-editable Awards teaser blurb (Landing Page Content CMS)
- [x] Public awards teaser (landing page) + `/events/{event}/awards` page shell
- [ ] Nominee entry (admin)
- [x] `Award` / `AwardVote` models, the voting window, and the one-vote-per-email rule
- [x] Decided: the public votes, one confirmed email per category
- [ ] Vote submission flow + results display

## 09 — QR System

- [x] QR code generated on ticket issuance (`endroid/qr-code`), emailed and shown on the ticket
- [x] Signed scan URL, so a QR cannot be forged or replayed from a guessed id
- [x] Check-in portal for staff (`/check-in/{event}`), behind login
- [x] Check-in marks the ticket Checked In, and refuses invalid, unpaid or already-used tickets
- [x] A designed ticket, printable from its own page and embedded in the issued email

## 10 — Reports

- [x] Funnel (requested → approved → paid → checked in) and counts for every ticket status
- [x] Revenue: collected, discounted, outstanding, and a breakdown by ticket type
- [x] Check-in on the day: attendance against issued tickets, and arrivals by hour
- [x] Coupon use
- [x] CSV export of the attendee list, BOM-prefixed so Excel reads Arabic names
- [x] Workshop attendance, and each attendee's workshops in the CSV export

## Not in any phase, still open

- [ ] The CCS event page still has hardcoded English strings that are not CMS-editable
      (the Creators Hub page was converted; CCS was deferred)
- [ ] Deployment follow-ups: set `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD`, deploy
      `public/.user.ini`, verify with `php artisan media:limits`
- [ ] Commits are unpushed

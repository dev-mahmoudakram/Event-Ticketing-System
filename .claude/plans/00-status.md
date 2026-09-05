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
| 02 | Database Design | 🟡 Partial — content, branding and ticket workflow schema done; booking/coupons/awards tables missing |
| 03 | Admin Panel | 🟡 Partial — every content CRUD and the approval queue done; coupons and reports missing |
| 04 | Landing Page | ✅ Done — both brands (CCS event pages and the Creators Hub platform page) |
| 05 | Ticket Request | ✅ Done — request, review, approve/reject, approval email with a payment link |
| 06 | Payment | 🟡 Partial — the full flow works on a stub link; no real gateway |
| 07 | Workshops | 🟡 Partial — browsing built, booking flow missing |
| 08 | Awards | 🟡 Partial — teaser and page shell built, voting missing |
| 09 | QR System | ✅ Done — QR on issuance, signed scan URL, staff check-in portal |
| 10 | Reports | ⬜ Not started |

Four phases complete, five partial, one untouched.

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
- [ ] WorkshopBooking (slot-based booking keyed by Ticket ID + Workshop Booking Key)
- [ ] DiscountCoupon
- [ ] Award / AwardVote

The `tickets.workshop_booking_key` column exists but nothing writes to it yet — it is issued as
part of Workshops (Phase 07).

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
- [ ] Discount Coupons admin
- [ ] Per-event Reports screen

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
- [ ] Real gateway (Kashier — waiting on their approval); `TicketPaymentController` marks a
      ticket paid without taking money
- [ ] Payment records: amount, currency, gateway reference, refunds
- [ ] Workshop Booking Key generation on payment success (belongs with Phase 07)

## 07 — Workshops

- [x] Admin CRUD for workshops
- [x] Public workshop browsing (`workshops.index`, `workshops.show`)
- [x] Landing page workshops teaser (capacity shown, no fabricated fill %)
- [ ] `WorkshopBooking` model
- [ ] Ticket ID + Workshop Booking Key redemption flow (no login)
- [ ] Enforce each ticket type's `workshop_slot_count` against booked slots

## 08 — Awards

- [x] Admin-editable Awards teaser blurb (Landing Page Content CMS)
- [x] Public awards teaser (landing page) + `/events/{event}/awards` page shell
- [ ] Voting mechanics decided (who can vote, one vote per category/person, voting window)
- [ ] Nominee entry (admin)
- [ ] `Award` / `AwardVote` models
- [ ] Vote submission flow + results display

## 09 — QR System

- [x] QR code generated on ticket issuance (`endroid/qr-code`), emailed and shown on the ticket
- [x] Signed scan URL, so a QR cannot be forged or replayed from a guessed id
- [x] Check-in portal for staff (`/check-in/{event}`), behind login
- [x] Check-in marks the ticket Checked In, and refuses invalid, unpaid or already-used tickets
- [x] A designed ticket, printable from its own page and embedded in the issued email

## 10 — Reports

- [ ] Metrics defined (ticket counts by status, revenue, workshop attendance, check-in rates)
- [ ] Per-event admin report screens
- [ ] Export (CSV) for the registration and finance teams

## Not in any phase, still open

- [ ] The CCS event page still has hardcoded English strings that are not CMS-editable
      (the Creators Hub page was converted; CCS was deferred)
- [ ] Deployment follow-ups: set `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD`, deploy
      `public/.user.ini`, verify with `php artisan media:limits`
- [ ] 14 commits are unpushed

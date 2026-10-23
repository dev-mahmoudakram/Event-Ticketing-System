# Award Voting — Design

**Date:** 2026-10-23
**Status:** Draft for approval
**Module:** Awards (`.claude/plans/08-awards.md`) — replaces the placeholder Awards page with real voting.

## Why

Each event (first: CCS) runs public awards: the organisers pick the awards and the nominees, and
the audience votes. The owner wants voting that is easy for real people — including a crowd on
the venue Wi-Fi — while being hard to stuff with fake votes. Live vote counts are part of the
show: people should see the race.

## Goals

- The admin creates awards per event and adds nominees inside each award.
- Anyone can vote, no account: pick a nominee → enter email → pass a Cloudflare captcha → receive
  a 6-digit code by email → enter it → the vote counts.
- One vote **per email per award**. The same email can vote in every award, once each.
- Live vote counts are public.
- Voting follows a schedule the admin sets **and** the admin can force it open or closed by hand.
- The admin can see every vote, spot suspicious ones, remove them, and export voters as CSV.
- Bilingual (Arabic RTL / English LTR), reusable across events, nothing CCS-specific hardcoded.

## Non-goals

- No limiting votes by IP address. Many people legitimately share one IP (venue Wi-Fi, offices,
  mobile carriers). IP is used only for rate limiting and for flagging.
- No requirement to hold a ticket to vote.
- No winners/announcement mode — counts are simply live.
- No SMS codes, no social login.

## Anti-goals (would be a failure even if it "works")

- A crowd at the venue being blocked because one person on the Wi-Fi already voted.
- Duplicate votes for the same email in the same award getting in through a direct request that
  skips the page.
- A removed (fraudulent) vote's email being able to vote again in that award.
- A code email that never arrives with no way to request another.

## Decisions made with the owner

| Topic | Decision |
|---|---|
| Who creates awards and nominees | The admin, per event. |
| Vote limit | One vote per email per award. |
| IP rule | Not a vote limit. Rate limiting + suspicious-activity flagging + Cloudflare captcha. |
| Captcha | Cloudflare Turnstile. Keys go in `.env` only (never in git); `.env.example` gets empty placeholders. |
| Uniqueness | Enforced by the database, not only the form. |
| Results | Live vote counts shown publicly next to each nominee. |
| Voting window | Both: a scheduled open/close per event **and** a manual override. |
| Page layout | **Layout A** — one award at a time, tabs across the top switch awards. |
| Vote steps | A small window over the page: 1 confirm choice → 2 email + captcha → "Send code" → 3 enter 6-digit code → 4 done, count updates, card shows "You voted". |
| Admin powers | Manage awards/nominees, remove suspicious votes, export voter emails. |
| Removed votes | The vote stops counting, but that email **stays blocked** in that award. |

## How it works

### Public: the Awards page (`/events/{event}/awards`)

- Styled like the rest of the CCS event pages (event nav + footer), replacing the current placeholder.
- The Awards teaser blurb (already in the Landing Page Content CMS) appears at the top.
- Tabs, one per award, in the admin's order. A deep link can open a specific award tab
  (builder's call on the URL shape, e.g. `#award-{id}` or `?award=`).
- Each nominee card: photo (or a neutral placeholder), name, vote bar, vote count, **Vote** button.
  Tapping the card shows the short bio when there is one.
- A status line: "Voting closes on …", "Voting opens on …", or "Voting is closed".
- Before voting opens: nominees and counts (zero) show, Vote buttons are hidden.
- After voting closes: final counts stay visible, Vote buttons hidden.
- If the event has no awards, the page says awards will be announced soon.
- Counts refresh on their own while the page is open (my call: poll every ~30 seconds, and
  immediately after the visitor's own vote). Only confirmed, not-removed votes count.
- "You voted" is remembered for that browser (session) once the code is confirmed. On another
  device the person is told the email has already voted when they try again.

### Public: the vote window

1. **Confirm** — "You're voting for {nominee} in {award}."
2. **Email + captcha** — email field and the Cloudflare Turnstile widget; "Send code".
   - Refused immediately (no code sent) if that email already has a vote in this award
     (including a removed one): "This email has already voted in this award."
   - Refused if voting isn't open.
   - Refused if the captcha fails.
3. **Enter the code** — 6 digits, sent to the email. "Resend code" link (rate limited).
   - A code is valid for **10 minutes** and allows **5 wrong attempts**, then the visitor must
     request a new one. (My calls.)
   - A code is tied to one email + one award + the chosen nominee.
   - My calls: **Resend** shows the Cloudflare check again (Turnstile tokens are single-use) and
     sends a fresh code. A new code cancels any earlier unused code for that email + award, so
     going back and picking a different nominee invalidates the old code.
4. **Done** — the vote is saved, the count goes up, the card shows "You voted".

The window works with the keyboard, in RTL and LTR, and on phones.

### The code email

- Subject and body in the language of the page the visitor was on.
- Shows the code, the award and the nominee, and that it expires in 10 minutes.
- Sent immediately (not queued behind a worker), like the other public-facing emails today.

### Protection against fake votes

- **Database:** one row per (award, email) in the votes table, with a unique index. Emails are
  stored lower-cased and trimmed so `Sara@X.com` and `sara@x.com ` are the same voter.
- **Captcha:** Turnstile token verified server-side before any code is sent. Tests must never
  call Cloudflare (fake the HTTP call).
- **Rate limiting (my calls, builder may tune):**
  - Send code: per IP 5 per minute and 30 per hour; per email 3 per 10 minutes.
  - Verify code: per IP 10 per minute; plus the 5-wrong-attempts limit per code.
- **Codes:** stored hashed, never in plain text; single use.
- **Flagging:** a vote is marked **Suspicious** in the admin when its IP address has 5 or more
  confirmed votes in the same award within 60 minutes (my call; one place to tune it).
- Every vote records its IP address and time.

### Real visitor IP behind Cloudflare

The live site is behind Cloudflare's proxy (orange cloud **on**, confirmed by the owner). Without
setup, every request appears to come from a handful of Cloudflare addresses, which would make the
per-IP limits block whole crowds and flag every vote as suspicious. So:

- Trust Cloudflare as a proxy only when the request actually comes from Cloudflare's published IP
  ranges, and then take the visitor's IP from Cloudflare's header (`CF-Connecting-IP`, or the
  forwarded-for header Laravel's trusted-proxy support reads). A request from anywhere else keeps
  its own address, so nobody can fake an IP by sending the header directly.
- Cloudflare's ranges are kept in the app's config (no new package); the builder decides how
  they're stored and refreshed.
- This fixes `$request->ip()` app-wide, so the existing ticket/sponsor/speaker/contact rate limits
  benefit too. Tests cover: a request from a Cloudflare range uses the header IP; a request from
  elsewhere ignores the header.
- Local development (no Cloudflare) keeps working unchanged.

### Voting window

- Per event: `voting_opens_at`, `voting_closes_at` (already exist) and a new manual mode with
  three values: **Follow the schedule** (default), **Open now**, **Closed**.
- "Follow the schedule" with no dates set counts as closed (current behaviour of
  `Event::votingIsOpen()`).
- Times are entered and shown in the app's timezone.

### Admin

New **Awards** item in each event's admin menu, behind a new **Awards** permission in the roles
matrix (group "Event setup"), deny-by-default like the other permissions.

- **Awards list:** add, edit, delete, drag to reorder. Fields: name (Arabic + English).
  Shows the number of nominees and confirmed votes per award.
- **Nominees (inside an award):** add, edit, delete, drag to reorder. Fields: name (Arabic +
  English), photo (existing media upload component), short bio (Arabic + English, optional).
  Shows each nominee's live count.
- Deleting an award or nominee that already has votes asks for confirmation and deletes its votes.
  (My call: deleting a nominee frees those voters to vote again for another nominee in that award,
  because the choice they made no longer exists; deleting an award removes the award entirely.
  Blocking after removal applies to the "Remove vote" action, not to deletes.)
- **Voting settings** (on the Awards list page): opens at, closes at, manual mode. Validation:
  close must be after open.
- **Votes list:** filter by award and nominee; columns email, nominee, IP address, time,
  Suspicious badge, status (counted / removed). Paginated.
  - Remove one vote, or remove all votes from one IP address in one award, with a confirmation.
  - Removed votes stay listed as "Removed" and stop counting; the email stays blocked.
- **Export CSV:** confirmed voters (email, award, nominee, time, IP, status) for the whole event or
  one award. Must open correctly in Excel with Arabic text (UTF-8 BOM).

## Data

The existing `awards` / `award_votes` tables were created for a different shape (each row was a
nominee carrying its own category; votes were confirmed by an emailed link). Nothing writes to
them yet — there has never been an admin screen or a voting form. They are reshaped:

- `awards` — an award: `event_id`, `name_ar`, `name_en`, `sort_order`.
- `award_nominees` — `award_id`, `name_ar`, `name_en`, `bio_ar`, `bio_en`, `image_path`, `sort_order`.
- `award_votes` — a confirmed vote: `event_id`, `award_id`, `award_nominee_id`, `email`,
  `ip_address`, `removed_at` (+ who removed it), timestamps. Unique `(award_id, email)`.
- Pending codes live separately from votes (a vote row exists only once confirmed), holding
  email, award, nominee, hashed code, attempts, expiry and IP.
- `events`: add the manual voting mode. `show_award_results` becomes unused (counts are always
  public) — my call: drop it.

The migration must refuse to run destructively if the old tables contain rows (safety for the
live server); the builder decides the exact mechanics.

## Constraints

- Laravel 12, PHP 8.3, MySQL (dev/live) and SQLite in-memory (tests), Blade + Alpine + Tailwind v4.
- Follow existing patterns: admin views/components (`x-admin.*`), `Permission` enum +
  `AdminPermissions`, sortable lists (`data-sortable`), `ResolvesStoredMedia`, translations in
  `lang/ar.json` and `lang/en.json` (coverage tests scan every key), CCS landing styles.
- No new Composer or npm packages; Turnstile is one script tag plus one HTTP check.
- PHPUnit feature tests for every path; `vendor/bin/pint --dirty --format agent`.
- After merging: `php artisan migrate --force` on the dev database.
- Never commit secrets. The Cloudflare keys are in `C:\Users\Vkram\Downloads\cloudflare-secrets.txt`;
  a script copies them into `.env` (e.g. `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, read via
  `config/services.php`) without printing them. The builder works out the file's format by
  parsing it in the script, not by displaying it. The live server's `.env` needs the same keys
  (the owner adds them).

## Left to the builder

- URL shapes for tabs, the vote endpoints and the admin screens.
- How the vote window talks to the server (fetch + JSON, as the ticket form does, is a good fit).
- How the count polling is done, within "about every 30 seconds".
- The exact "Suspicious" query, as long as the rule above holds.
- Visual details within the existing CCS style and Layout A.

## Out of scope / later

- Upgrading `sharp` (the `npm audit` warning) — separate task.
- Payment gateway integration.

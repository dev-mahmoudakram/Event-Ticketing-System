# Award Voting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the placeholder Awards page with real public voting — admin-managed awards and nominees, email one-time codes behind a Cloudflare Turnstile check, one vote per email per award, live counts, and admin vote moderation and CSV export.

**Architecture:** Reshape the unused `awards`/`award_votes` tables into awards → nominees → votes, with pending codes in their own table. A single `AwardVoting` service owns the rules (window, uniqueness, codes); thin controllers serve a fetch/JSON vote window on the public page and standard CRUD screens in the admin. Cloudflare is trusted as a proxy so `$request->ip()` is the visitor's real address.

**Tech Stack:** Laravel 12, PHP 8.3, MySQL (dev/live) + SQLite in-memory (tests), Blade, Alpine, Tailwind v4, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-23-award-voting-design.md`

## Global Constraints

- One vote per email per award, enforced by a unique index `(award_id, email)`; emails stored lower-cased and trimmed.
- IP is never a vote limit — only rate limiting and flagging.
- Codes: 6 digits, stored hashed, single use, valid **10 minutes**, **5 wrong attempts** then dead; a new code cancels earlier unused codes for that email + award.
- Rate limits: send code — per IP 5/minute and 30/hour, per email 3 per 10 minutes; verify — per IP 10/minute.
- Suspicious: an IP with **5 or more** counted votes in the same award within **60 minutes**.
- "Remove vote" keeps the email blocked (row stays, `removed_at` set, not counted). Deleting a nominee/award deletes its vote rows.
- Voting mode per event: `schedule` (default; no dates = closed), `open`, `closed`.
- Live counts public; refresh about every 30 seconds and right after the visitor's own vote.
- Layout A: one award at a time, tabs.
- No new Composer/npm packages. Turnstile = Cloudflare script tag + one server-side HTTP check; tests use `Http::fake()` and never call Cloudflare.
- Secrets: Turnstile keys from `C:\Users\Vkram\Downloads\cloudflare-secrets.txt` into `.env` as `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` by a script that never prints them; `.env.example` gets empty placeholders.
- Bilingual: every string through `__()`, Arabic in `lang/ar.json`, key added to `lang/en.json` (coverage tests scan literals).
- Never author/co-author commits as Claude; no Co-Authored-By line. Run `vendor/bin/pint --dirty --format agent` before each commit. Write scripts with the Write tool (Git Bash heredocs strip backslashes).
- Do not delete tests; existing award tests in `tests/Feature/NewSchemaTest.php` are rewritten for the new shape.

## Review Focus

1. **The same voter typed differently** (`Sara@X.com ` vs `sara@x.com`) must be one voter — Task 4 `test_email_case_and_spaces_are_the_same_voter`.
2. **Two codes confirmed for the same email and award** (two tabs, a race) must give "already voted", never a 500 — Task 4 `test_a_second_confirmation_is_refused_cleanly`.
3. **A removed vote** must stop counting and keep the email blocked — Task 4 `test_a_removed_vote_keeps_the_email_blocked`.
4. **A nominee from another award, or an award from another event,** must be 404 — Task 5 `test_a_nominee_from_another_award_is_not_found`.
5. **Voting closing between sending and confirming the code** must refuse the vote — Task 4 `test_a_code_cannot_be_used_after_voting_closes`.

---

## File Structure

| File | Responsibility |
|---|---|
| `config/cloudflare.php` | Cloudflare IPv4/IPv6 ranges trusted as proxies |
| `bootstrap/app.php` | `trustProxies(at: config ranges)` |
| `database/migrations/*_reshape_awards_for_voting.php` | Reshape tables, add `events.voting_mode`, drop `show_award_results` |
| `app/Enums/VotingMode.php` | `schedule` / `open` / `closed` |
| `app/Models/Award.php`, `AwardNominee.php`, `AwardVote.php`, `AwardVoteCode.php` | Data |
| `app/Services/AwardVoting.php` | All voting rules |
| `app/Services/SuspiciousVotes.php` | The flag rule |
| `app/Services/TurnstileVerifier.php` | Server-side captcha check |
| `app/Mail/AwardVoteCodeMail.php` + `resources/views/emails/awards/code.blade.php` | Code email |
| `app/Http/Controllers/AwardsController.php`, `AwardVoteController.php` | Public page, counts JSON, send code, confirm |
| `app/Http/Controllers/Admin/AwardController.php`, `AwardNomineeController.php`, `AwardVoteController.php` | Admin |
| `resources/views/awards/show.blade.php` (+ partials) | Public page, Layout A, vote window |
| `resources/js/award-voting.js` | Vote window + count polling |
| `resources/views/admin/awards/*`, `admin/award-votes/index.blade.php` | Admin screens |

---

### Task 1: Real visitor IP behind Cloudflare

**Files:**
- Create: `config/cloudflare.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/CloudflareProxyTest.php`

**Interfaces:**
- Produces: `config('cloudflare.proxies')` — `list<string>` of CIDR ranges; `$request->ip()` is the real visitor IP when the request comes from one of them.

- [ ] **Step 1: Failing tests** — `CloudflareProxyTest`, each registering a throwaway route `Route::get('/_ip', fn (Request $r) => $r->ip())`:
  - `test_a_request_through_cloudflare_uses_the_visitors_ip`: `$this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])->withHeader('X-Forwarded-For', '41.33.10.5')->get('/_ip')->assertSeeText('41.33.10.5')`
  - `test_a_direct_request_cannot_fake_its_ip`: `REMOTE_ADDR` `203.0.113.7`, same header → `assertSeeText('203.0.113.7')`
  - `test_ipv6_cloudflare_ranges_are_trusted`: `REMOTE_ADDR` `2400:cb00::1`, header `41.33.10.5` → `41.33.10.5`
- [ ] **Step 2:** `php artisan test --compact tests/Feature/CloudflareProxyTest.php` → first and third FAIL.
- [ ] **Step 3:** `config/cloudflare.php` returns `['proxies' => [...]]` with Cloudflare's published ranges (https://www.cloudflare.com/ips-v4 and ips-v6 — copy the current lists; IPv4 includes `173.245.48.0/20`, IPv6 includes `2400:cb00::/32`), with a comment naming those URLs as the source to refresh from. In `bootstrap/app.php` `withMiddleware`: `$middleware->trustProxies(at: fn () => config('cloudflare.proxies'), headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);` (check `search-docs`/framework source for whether `at:` accepts a closure in this version; if not, read the config file directly with `require`).
- [ ] **Step 4:** Tests PASS; also run `tests/Feature/PublicFormRateLimitTest.php` → PASS.
- [ ] **Step 5:** pint; commit `feat: read the real visitor IP behind Cloudflare`.

---

### Task 2: Reshape the awards data

**Files:**
- Create: migration `reshape_awards_for_voting`, `app/Enums/VotingMode.php`, `app/Models/AwardNominee.php`, `app/Models/AwardVoteCode.php`, `database/factories/AwardNomineeFactory.php`, `database/factories/AwardVoteFactory.php`
- Modify: `app/Models/Award.php`, `app/Models/AwardVote.php`, `app/Models/Event.php`, `database/factories/AwardFactory.php`
- Test: `tests/Feature/AwardModelTest.php` (new); rewrite the award tests in `tests/Feature/NewSchemaTest.php`

**Interfaces:**
- Produces:
  - `enum VotingMode: string { case Schedule = 'schedule'; case Open = 'open'; case Closed = 'closed'; public function label(): string }`
  - `events.voting_mode` (string, default `'schedule'`, cast `VotingMode`); `show_award_results` dropped.
  - `Event::votingIsOpen(): bool` — `Open` → true, `Closed` → false, `Schedule` → existing window rule.
  - `Award` (`event_id`, `name_ar`, `name_en`, `sort_order`): `name(): string`, `nominees(): HasMany` (ordered `sort_order`, `id`), `votes(): HasMany`.
  - `AwardNominee` (`award_id`, `name_ar`, `name_en`, `bio_ar`, `bio_en`, `image_path`, `sort_order`; `ResolvesStoredMedia`): `name()`, `bio(): ?string`, `imageUrl(): ?string`, `award()`, `votes()`.
  - `AwardVote` (`event_id`, `award_id`, `award_nominee_id`, `email`, `ip_address`, `removed_at` datetime, `removed_by` nullable FK users nullOnDelete): `scopeCounted()` (`whereNull('removed_at')`), `isRemoved(): bool`, relations `award()`, `nominee()`. Unique `(award_id, email)`; index `(award_id, ip_address, created_at)`.
  - `AwardVoteCode` (`award_id`, `award_nominee_id`, `email`, `code_hash`, `attempts` unsigned tiny default 0, `expires_at`, `ip_address`): index `(award_id, email)`. FKs cascade on delete.
  - All award FKs cascade on delete (event → awards → nominees/votes/codes).

- [ ] **Step 1: Failing tests** — `AwardModelTest`:
  - `test_an_award_lists_its_nominees_in_order` (sort_order 2 then 1 → returned 1, 2)
  - `test_one_email_votes_once_per_award`: two `AwardVote` rows same award + email → `QueryException`
  - `test_the_same_email_votes_in_another_award`: second award, same email → succeeds
  - `test_removed_votes_do_not_count`: `AwardVote::counted()->count()` excludes a row with `removed_at`
  - `test_the_manual_mode_overrides_the_schedule`: window in the past + `Open` → true; window now + `Closed` → false; `Schedule` + no dates → false
  - `test_deleting_a_nominee_deletes_its_votes`
  Rewrite `NewSchemaTest::test_a_nominee_belongs_to_a_category`, `test_one_email_votes_once_per_category`, `test_the_same_email_votes_in_a_different_category` to the new shape (award → nominee; uniqueness per award) keeping their intent; keep `test_voting_is_shut_until_a_window_is_set`.
- [ ] **Step 2:** `php artisan test --compact tests/Feature/AwardModelTest.php tests/Feature/NewSchemaTest.php` → FAIL.
- [ ] **Step 3: Migration** — `up()`: if `DB::table('awards')->exists() || DB::table('award_votes')->exists()` throw `RuntimeException('awards/award_votes already hold data; reshape them by hand before migrating.')`; drop `award_votes`, `awards`; create the four tables above; add `voting_mode` after `voting_closes_at`; drop `show_award_results`. `down()`: reverse to the old shapes (copy column definitions from `2026_09_05_205752_create_awards_table.php` and `..._205754_create_award_votes_table.php`). Then models, enum, factories (`AwardNomineeFactory` for an award; `AwardVoteFactory` with `removed()` state, `email` unique, `ip_address` faker IPv4). Remove `show_award_results` from `Event` fillable/casts; add `voting_mode`.
- [ ] **Step 4:** Tests PASS; full suite green (log to a file, read the tail).
- [ ] **Step 5:** pint; commit `feat: reshape awards into awards, nominees and votes`.

---

### Task 3: Admin — awards, nominees and voting settings

**Files:**
- Create: `app/Http/Controllers/Admin/AwardController.php`, `AwardNomineeController.php`, `app/Http/Requests/Admin/AwardRequest.php`, `AwardNomineeRequest.php`, `AwardVotingSettingsRequest.php`, views `resources/views/admin/awards/{index,form,nominees,nominee-form}.blade.php`
- Modify: `app/Enums/Permission.php`, `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php`, `lang/*.json`
- Test: `tests/Feature/Admin/AwardCrudTest.php`; add rows to `tests/Unit/AdminPermissionsTest.php`

**Interfaces:**
- Consumes: Task 2 models, `VotingMode`.
- Produces: `Permission::Awards` (`'awards'`, label `__('Awards')`, group "Event setup", routes `['admin.events.awards.*', 'admin.events.award-votes.*']`, entry `admin.events.awards.index`). Routes: `admin.events.awards.{index,create,store,edit,update,destroy}` (param `award`), `admin.events.awards.reorder` (POST), `admin.events.awards.settings` (PUT), `admin.events.awards.nominees.{index,create,store,edit,update,destroy}` (params `award`, `nominee`), `admin.events.awards.nominees.reorder` (POST). Sidebar row after Pages.

- [ ] **Step 1: Failing tests** — `AwardCrudTest` (admin = `User::factory()->create()`, like `EventPageCrudTest`):
  - `test_an_admin_adds_edits_reorders_and_deletes_an_award`
  - `test_the_list_shows_nominee_and_vote_counts` (2 nominees, 3 counted votes + 1 removed → sees `2` and `3`)
  - `test_an_admin_manages_nominees_with_a_photo` (`Storage::fake`, `UploadedFile::fake()->image('n.jpg')`; follow `SpeakerController`'s `HandlesMediaUploads` use; photo path stored; delete removes file)
  - `test_another_events_award_is_not_found` and `test_another_awards_nominee_is_not_found` → 404
  - `test_voting_settings_save_and_close_must_follow_open` (`voting_mode=open` saves; closes ≤ opens → error on `voting_closes_at`)
  - `test_the_awards_permission_opens_the_section` (`withPermissions(Permission::Awards)` → 200; `Speakers` → 403)
  - `AdminPermissionsTest` rows: `'awards' => ['admin.events.awards.edit', Permission::Awards]`, `'award nominees' => ['admin.events.awards.nominees.index', Permission::Awards]`, `'award votes' => ['admin.events.award-votes.index', Permission::Awards]`.
- [ ] **Step 2:** Run them → FAIL.
- [ ] **Step 3:** Implement following `Admin\EventPageController` (assert-belongs → 404, `sort_order = max + 1`, reorder validated with `Rule::exists(...)->where(...)`) and the sortable table pattern. Awards index: table (name, nominees, votes, actions) + the voting settings form (datetime-local opens/closes, select for mode with `VotingMode::label()`) + link "Votes". Nominee bio: `x-admin.bilingual-field type="textarea"` (plain text). The nominees screen shows each nominee's counted votes. Deletes use `data-confirm` with `__('This also deletes its votes. Are you sure?')` when it has votes.
- [ ] **Step 4:** Tests PASS; `HubTranslationCoverageTest` PASS.
- [ ] **Step 5:** pint; commit `feat: let admins manage awards, nominees and the voting window`.

---

### Task 4: The voting rules (service, codes, Turnstile, email)

**Files:**
- Create: `app/Services/AwardVoting.php`, `app/Services/TurnstileVerifier.php`, `app/Exceptions/VotingRefused.php`, `app/Mail/AwardVoteCodeMail.php`, `resources/views/emails/awards/code.blade.php`
- Modify: `config/services.php`, `.env.example`, `.env` (via script), `lang/*.json`
- Test: `tests/Feature/AwardVotingServiceTest.php`, `tests/Feature/TurnstileVerifierTest.php`

**Interfaces:**
- Consumes: Task 2 models.
- Produces:
  - `config('services.turnstile.site_key')`, `config('services.turnstile.secret_key')`.
  - `TurnstileVerifier::verify(?string $token, ?string $ip): bool` — POST `https://challenges.cloudflare.com/turnstile/v0/siteverify` (`secret`, `response`, `remoteip`), true only when JSON `success === true`; false on missing token, HTTP error or timeout (5 s).
  - `final class VotingRefused extends RuntimeException` — message is the user-facing translated text.
  - `AwardVoting::normalizeEmail(string $email): string` (static; `mb_strtolower(trim())`)
  - `AwardVoting::sendCode(Award $award, AwardNominee $nominee, string $email, string $ip): void` — throws `VotingRefused` with `__('Voting is not open.')` / `__('This email has already voted in this award.')` / `__('Too many codes were requested for this email. Try again in a few minutes.')` (3 per 10 minutes per email+award). Deletes earlier codes for email+award, stores `Hash::make($code)` with `expires_at = now()+10min`, mails `AwardVoteCodeMail` immediately in the current locale.
  - `AwardVoting::confirm(Award $award, string $email, string $code, string $ip): AwardVote` — throws `VotingRefused` with `__('Voting is not open.')` / `__('This email has already voted in this award.')` / `__('That code has expired. Request a new one.')` (missing or expired, or attempts ≥ 5) / `__('That code is not right.')` (increments `attempts`). On success: creates the vote (catching `UniqueConstraintViolationException` → already voted), deletes the code.
  - `AwardVoting::counts(Event $event): array<int, int>` — nominee id → counted votes, zero-filled for every nominee.
  - `AwardVoteCodeMail(string $code, Award $award, AwardNominee $nominee)` — subject `__('Your voting code: :code', …)`; body shows code, award, nominee, "expires in 10 minutes".

- [ ] **Step 1: Failing tests** — `AwardVotingServiceTest` (`Mail::fake()`; voting `Open`; capture the code from the faked mailable's `code` property):
  - `test_sending_a_code_mails_it_and_stores_it_hashed` (stored `code_hash` ≠ code; `Hash::check` true)
  - `test_a_correct_code_records_one_vote_with_the_ip`
  - `test_email_case_and_spaces_are_the_same_voter` (code for `Sara@X.com ` → vote email `sara@x.com`; then `sendCode` for `sara@x.com` → already voted)
  - `test_a_second_confirmation_is_refused_cleanly` (two codes can't coexist via sendCode, so insert a vote directly for the email then `confirm` a valid code row → `VotingRefused` already voted, no exception escapes)
  - `test_a_removed_vote_keeps_the_email_blocked` (`AwardVote::factory()->removed()` → `sendCode` refused; `counts()` shows 0)
  - `test_a_wrong_code_counts_attempts_and_the_fifth_kills_it` (5 wrong → 6th try with the right code → expired message)
  - `test_a_code_expires_after_ten_minutes` (`travel(11)->minutes()`)
  - `test_a_new_code_cancels_the_old_one`
  - `test_a_code_cannot_be_used_after_voting_closes` (send, then set mode `Closed`, confirm → refused)
  - `test_an_email_can_ask_for_three_codes_per_ten_minutes`
  - `test_counts_cover_every_nominee`
  `TurnstileVerifierTest` with `Http::fake`: success true → true; success false → false; missing token → false and no request sent (`Http::assertNothingSent()`); connection exception → false.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement. Keys: write `scratchpad/turnstile_env.py` that reads `C:\Users\Vkram\Downloads\cloudflare-secrets.txt`, finds the site key and secret key (parse `key=value` / `key: value` lines whose name contains "site" / "secret"; exit with an error message naming the line labels found — never values — if it can't tell), and upserts `TURNSTILE_SITE_KEY=` / `TURNSTILE_SECRET_KEY=` in `.env`; print only `set TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY`. Add empty placeholders to `.env.example`. Confirm `.env` is git-ignored (`git check-ignore .env`).
- [ ] **Step 4:** Tests PASS; full suite green.
- [ ] **Step 5:** pint; commit `feat: email voting codes behind a Cloudflare check` (stage only tracked files — never `.env`).

---

### Task 5: Public voting endpoints and rate limits

**Files:**
- Create: `app/Http/Controllers/AwardVoteController.php`, `app/Http/Requests/AwardCodeRequest.php`, `AwardConfirmRequest.php`
- Modify: `app/Providers/AppServiceProvider.php` (limiters), `routes/web.php`, `app/Http/Controllers/AwardsController.php`
- Test: `tests/Feature/AwardVotingEndpointsTest.php`

**Interfaces:**
- Consumes: `AwardVoting`, `TurnstileVerifier` (bind a fake in tests: `$this->mock(TurnstileVerifier::class)` or `Http::fake`).
- Produces (inside the `events/{event}` + `EnsureEventIsPublished` group):
  - `GET /awards/counts` → `awards.counts`: `{"counts": {"<nomineeId>": n}, "open": bool}`
  - `POST /awards/{award}/code` → `awards.code` (`throttle:award-code`): body `nominee_id`, `email`, `cf-turnstile-response` → 200 `{"message": …}`; 422 `{"errors": {field: [msg]}}` for validation, captcha (`errors.captcha`) and `VotingRefused` (`errors.email`).
  - `POST /awards/{award}/vote` → `awards.vote` (`throttle:award-verify`): body `email`, `code` → 200 `{"message", "nominee_id", "count"}`; 422 with `errors.code` (or `errors.email` for already voted). On success pushes the award id onto session `awarded.{event_id}` (list of award ids voted from this browser).
  - Limiters: `award-code` = per IP 5/min + 30/hour; `award-verify` = per IP 10/min; both respond JSON 429 `{"message": __('Too many attempts. Please wait a minute and try again.')}`.
  - Award must belong to the event and `nominee_id` must belong to the award (`Rule::exists('award_nominees','id')->where('award_id', …)`), else 404 / 422.

- [ ] **Step 1: Failing tests:**
  - `test_a_visitor_gets_a_code_and_votes` (full happy path through HTTP; count becomes 1; session has the award id)
  - `test_a_failed_captcha_sends_no_code` (`Mail::assertNothingSent()`, `errors.captcha`)
  - `test_a_nominee_from_another_award_is_not_found` (422 on `nominee_id`) and `test_an_award_from_another_event_is_not_found` (404)
  - `test_voting_closed_is_refused` (mode `Closed` → 422 `errors.email` "Voting is not open.")
  - `test_the_sixth_code_request_in_a_minute_is_throttled` (vary emails, same IP → 6th is 429)
  - `test_many_people_on_one_ip_can_all_vote` (10 different emails from one IP across 2 minutes via `travel` → 10 counted votes)
  - `test_counts_are_public_json`
  - `test_a_draft_event_has_no_voting` (unpublished → 404)
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Tests PASS.
- [ ] **Step 5:** pint; commit `feat: public endpoints for sending codes and casting votes`.

---

### Task 6: The public Awards page (Layout A)

**Files:**
- Modify: `app/Http/Controllers/AwardsController.php`, `resources/views/awards/show.blade.php`, `resources/views/layouts/app.blade.php` only if a per-page script stack is missing, `resources/js/app.js` (import), `vite.config.js` only if needed, `lang/*.json`
- Create: `resources/views/awards/partials/vote-window.blade.php`, `resources/js/award-voting.js`
- Test: `tests/Feature/AwardsPageTest.php`

**Interfaces:**
- Consumes: Task 5 routes, `AwardVoting::counts()`, session `awarded.{event_id}`, `config('services.turnstile.site_key')`.
- Produces: the page `awards.show` with `data-award-voting` root carrying `data-counts-url`, `data-code-url-template`, `data-vote-url-template` (with an `__AWARD__` token), and `data-site-key`.

Page rules (from the spec): event nav + footer like `event-pages/show.blade.php`; teaser blurb (rich text, `ccs-richtext`) at the top; status line ("Voting closes on :date" / "Voting opens on :date" / "Voting is closed"); tabs one per award in order with a deep link via `#award-{id}`; nominee cards (photo or neutral placeholder, name, bar, count, Vote button only while open; "You voted" when the award id is in the session; tap shows bio); empty state "Awards will be announced soon." when no awards. Vote window: 4 steps (confirm → email + Turnstile + "Send code" → 6-digit code + "Resend code" which re-shows the Turnstile widget → done), keyboard-focus trapped and Escape closes, works RTL/LTR and on phones; errors shown from 422 JSON under the matching field. Turnstile script `https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit` loaded only on this page; extend the CSP in `SecurityHeaders` for `challenges.cloudflare.com` (script-src and frame-src). Counts poll `awards.counts` every 30 s while the tab is visible, and update right after a successful vote.

- [ ] **Step 1: Failing tests** — `AwardsPageTest`:
  - `test_the_page_shows_tabs_nominees_and_counts` (2 awards; `assertSeeInOrder` award names; nominee names; a count)
  - `test_vote_buttons_show_only_while_voting_is_open` (`data-vote-nominee` present for `Open`, absent for `Closed`)
  - `test_the_status_line_follows_the_schedule` (future window → "Voting opens on"; current → "Voting closes on")
  - `test_a_browser_that_voted_sees_you_voted` (`withSession(['awarded.'.$event->id => [$award->id]])` → "You voted")
  - `test_the_page_without_awards_says_so`
  - `test_the_turnstile_site_key_is_on_the_page` (`config(['services.turnstile.site_key' => 'test-site-key'])`)
  - `test_the_csp_allows_cloudflare_turnstile` (response header contains `challenges.cloudflare.com`)
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement; `npm run build`.
- [ ] **Step 4:** Tests PASS plus `tests/Feature/LandingPageTest.php`, `HubTranslationCoverageTest`, and any CSP/security-header test → PASS.
- [ ] **Step 5:** pint; commit `feat: the public Awards page with live counts and a vote window`.

---

### Task 7: Admin — votes, suspicious flags, removal and export

**Files:**
- Create: `app/Services/SuspiciousVotes.php`, `app/Http/Controllers/Admin/AwardVoteController.php`, `resources/views/admin/award-votes/index.blade.php`
- Modify: `routes/web.php`, `resources/views/admin/awards/index.blade.php` (link), `lang/*.json`
- Test: `tests/Feature/Admin/AwardVoteModerationTest.php`, `tests/Unit/SuspiciousVotesTest.php` (feature-style with DB if simpler)

**Interfaces:**
- Consumes: Task 2 models, `Permission::Awards`.
- Produces:
  - `SuspiciousVotes::THRESHOLD = 5`, `WINDOW_MINUTES = 60`; `SuspiciousVotes::ipsFor(Award $award): Collection<int, string>` — IPs with ≥ THRESHOLD counted votes in that award inside any 60-minute window; `isSuspicious(AwardVote $vote): bool` via that set.
  - Routes: `admin.events.award-votes.index` (GET, filters `award`, `nominee`, paginated 50), `admin.events.award-votes.destroy` (DELETE `{vote}` → sets `removed_at`, `removed_by`), `admin.events.award-votes.destroy-ip` (POST `award`, `ip` → marks all counted votes from that IP in that award removed), `admin.events.award-votes.export` (GET, optional `award`, streamed CSV with UTF-8 BOM like `EventReportController::export`; columns Email, Award, Nominee, Time, IP, Status).

- [ ] **Step 1: Failing tests:**
  - `test_five_votes_from_one_ip_within_an_hour_are_suspicious` (4 → not; 5 within 60 min → yes; 5 spread over 3 hours → not)
  - `test_the_votes_list_filters_and_flags` (sees email, IP, "Suspicious" badge)
  - `test_removing_a_vote_stops_counting_and_keeps_the_email_blocked` (count drops; `AwardVoting::sendCode` for that email refused)
  - `test_removing_all_votes_from_an_ip_in_an_award` (other award's votes from the same IP untouched)
  - `test_export_lists_voters_with_a_bom` (`streamedContent()` starts with `"\xEF\xBB\xBF"`, contains email and nominee)
  - `test_another_events_vote_is_not_found` → 404
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement (removals behind `data-confirm`).
- [ ] **Step 4:** Tests PASS; full suite green.
- [ ] **Step 5:** pint; commit `feat: let admins review, remove and export award votes`.

---

### Task 8: Dev database, keys and a browser check

- [ ] **Step 1:** `php artisan migrate --force`; `php artisan tinker --execute 'echo Schema::hasTable("award_nominees") ? "ok" : "missing";'` → `ok`.
- [ ] **Step 2:** Confirm `.env` has both Turnstile keys set (check presence only: `grep -c "^TURNSTILE_SITE_KEY=." .env`) and `php artisan config:clear`.
- [ ] **Step 3:** `npm run build`; start `php artisan serve` if not running.
- [ ] **Step 4:** Playwright (scratchpad): as admin create an award with 3 nominees on `ccs-2026`, set mode Open; open `/events/ccs-2026/awards` in AR and EN (screenshots: tabs, cards, counts); open the vote window and walk steps 1–2 (screenshot; with `MAIL_MAILER=log` read the code from `storage/logs/laravel.log`). Cloudflare's widget may not pass in headless Chromium — if it blocks, use Cloudflare's documented always-pass test site key `1x00000000000000000000AA` / secret `1x0000000000000000000000000000000AA` temporarily via env override for the check, then restore. Enter the code, confirm count + "You voted"; admin votes list shows the vote; export downloads. Report page errors. Clean up the test award afterwards.
- [ ] **Step 5:** `php artisan test --compact` → green; report count. Update `.claude/plans/08-awards.md` and `.claude/plans/00-status.md` to mark voting built; commit `docs: mark award voting as built`.

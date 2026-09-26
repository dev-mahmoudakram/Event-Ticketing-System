# Code / Functional / Performance Review — Event Invitations (commits `4f3ff4c`, `1d00644`)

Scope: only the code added or changed by the two commits (34 + 5 files). Audit prompt: `laravel-mvc/01-code-functional-performance-audit-claude-code.md`, applied to a commit range. Read-only — no source changes were made. Companion files: `code-review-findings.json`, `SECURITY_AUDIT_REPORT.md`, `PLAN_VS_IMPLEMENTATION.md`.

## 1. Executive Summary

The invitations feature works end to end and is well built. Admins can generate a link + OTP, invitees verify and submit once, and admins approve (a real ticket is issued with QR code and workshop key, then emailed) or reject (a rejection email is sent). Concurrency is handled deliberately: submissions and reviews both lock the row and re-check state, and a unique FK enforces single use. All 17 invitation tests pass, as does the full suite (660).

**One High-impact defect:** invitation tickets are saved at the full ticket-type price with `is_paid = true`, so every free invited guest is counted as collected revenue in the event and platform reports. This came from the spec and plan, not from an implementation slip.

**Production readiness for this feature: YES WITH MINOR ISSUES** — fix INV-C01 (revenue) before relying on reports; the rest are improvements.

| Severity | Count |
|---|---|
| High | 1 |
| Medium | 1 |
| Low | 5 |
| Informational | 2 |

## 2. Application Architecture (as changed)

```mermaid
flowchart LR
  A[Admin] -->|POST generate| IC[Admin\InvitationController]
  IC --> INV[(invitations)]
  G[Invitee] -->|GET /invite/token| PIC[InvitationController@show]
  G -->|POST otp, throttle ip+token| PIV[InvitationController@verify]
  PIV -->|session invitation_verified.event| S[(session)]
  G -->|POST form| IRC[InvitationRequestController@store]
  IRC -->|lock invitation, create request, mark Used| IR[(invitation_requests)]
  IRC -->|mail| M1[InvitationRequestReceived]
  A -->|PATCH approve/reject| AIRC[Admin\InvitationRequestController]
  AIRC -->|lock request, Pending only| IR
  AIRC -->|approve: create ticket TicketIssued| T[(tickets)]
  AIRC -->|WorkshopBooker, TicketQrCode| T
  AIRC -->|mail inside txn| M2[TicketIssued / InvitationRequestRejected]
  T --> R[EventReport / PlatformReport revenue]
```

## 3. Route & Feature Inventory (new routes)

| Route | Middleware | Controller | Validation | Auth | Tests |
|---|---|---|---|---|---|
| `GET admin/events/{event}/invitations` | web, auth | `Admin\InvitationController@index` | — | any admin | `InvitationCrudTest` |
| `POST admin/events/{event}/invitations` | web, auth | `@store` | `InvitationStoreRequest` (active type in event) | any admin | yes |
| `PATCH admin/events/{event}/invitations/{invitation}/revoke` | web, auth | `@revoke` | event ownership check | any admin | yes |
| `GET admin/events/{event}/invitation-requests` | web, auth | `Admin\InvitationRequestController@index` | — | any admin | yes |
| `PATCH admin/events/{event}/invitation-requests/{invitationRequest}/{status}` | web, auth | `@updateStatus` | `in:approved,rejected` + Pending lock | any admin | yes |
| `GET events/{event}/invite/{token}` | web, published event | `InvitationController@show` | — | public | yes |
| `POST events/{event}/invite/{token}` | web, published event, `throttle:invitation-otp` | `@verify` | `digits:6` | public | yes (incl. 429) |
| `GET events/{event}/invite/{token}/request` | web, published event | `InvitationRequestController@create` | session check | public | yes |
| `POST events/{event}/invite/{token}/request` | web, published event | `@store` | `InvitationRequestStoreRequest` | public + session | yes |

## 4. Functional Verification Matrix

| Journey | Status | Evidence |
|---|---|---|
| Generate invitation (active type only) | VERIFIED WORKING | `tests/Feature/Admin/InvitationCrudTest.php:19,40` |
| Revoke unused / cannot revoke other event's | VERIFIED WORKING | `InvitationCrudTest.php:56,71` |
| Correct OTP → session → form | VERIFIED WORKING | `tests/Feature/InvitationVerificationTest.php:19` |
| Wrong OTP rejected, 6th attempt → 429 | VERIFIED WORKING | `InvitationVerificationTest.php:32` |
| Unknown/expired/used/revoked → identical page | VERIFIED WORKING | `InvitationVerificationTest.php:47` |
| Token from another event doesn't resolve | VERIFIED WORKING | `InvitationVerificationTest.php:64` |
| Submit once; second submit rejected | VERIFIED WORKING | `tests/Feature/InvitationRequestSubmissionTest.php:32` |
| "Other" category / required category / bad URL / negative followers | VERIFIED WORKING | `InvitationRequestSubmissionTest.php:32,63` |
| Approve → one TicketIssued ticket, QR secret, workshop key, email; re-approve is a no-op | VERIFIED WORKING | `tests/Feature/Admin/InvitationRequestQueueTest.php:26` |
| Reject → email, no ticket | VERIFIED WORKING | `InvitationRequestQueueTest.php:51` |
| Mail failure → ticket rolled back, request stays Pending | VERIFIED WORKING | `InvitationRequestQueueTest.php:65` |
| Emails render | VERIFIED WORKING | `InvitationRequestQueueTest.php:89` |
| Issued invitation ticket admitted at check-in | PARTIALLY VERIFIED | Statically: ticket has `is_paid=true`, `status=TicketIssued`, 40-char `ticket_id` — exactly what `app/Services/TicketCheckIn.php:49-58` requires. Not covered by a test. |
| Reports after an invitation is approved | FAILED (by design defect) | See INV-C01 |
| Phone widget (intl-tel-input) on invite form | PARTIALLY VERIFIED | Input has `id="phone"`, so `resources/js/app.js:34-55` attaches and normalises to E.164 on submit. Not browser-tested. |
| Arabic UI of the new screens | PARTIALLY VERIFIED | 8 strings fall back to English (INV-C04) |

## 5. Confirmed Bugs

### INV-C01 — Invitation tickets are counted as collected revenue (High, Correctness)
- **Expected:** a free invited guest adds 0 to "collected" revenue.
- **Actual:** approval saves `price = ticket type price`, `discount_amount = 0`, `is_paid = true` (`app/Http/Controllers/Admin/InvitationRequestController.php:71,72,77`). Revenue is `sum(price) − sum(discount_amount)` over `is_paid` tickets in `app/Services/EventReport.php:75` (collected), `:94` (per ticket type), and `app/Services/PlatformReport.php:43,66,171`. Each invitation therefore adds the full ticket price to revenue on the event report, the per-type table, the platform dashboard and the monthly chart.
- **Root cause:** spec and plan specified this price rule (`PLAN_VS_IMPLEMENTATION.md` §7).
- **Fix:** set `discount_amount` equal to `price` (net 0, list price preserved), or exclude `payment_method = 'invitation'` from revenue sums. Add a test that approves one invitation and asserts `EventReport::revenue()['collected'] === 0`.

## 6. MVC / Blade Findings

- **INV-C05 (Low, UX)** — the success banner on the landing page (`resources/views/landing/show.blade.php:20-24`) is `position: fixed` with no close control or timeout, so it covers the hero until the visitor navigates away.
- **INV-C04 (Low, Localization)** — strings used by the new screens with no Arabic entry in `lang/ar.json`: `Category`, `Social` (`resources/views/admin/invitation-requests/index.blade.php:19`), `We received your request` (`app/Mail/InvitationRequestReceived.php:26`), `We received your request.` (`resources/views/emails/invitation-requests/received.blade.php:3,7`), `Influencer Category` (`resources/views/invitations/create.blade.php:22`), `Other` (`:32`), `Tell us your category` (`:36`), and `TikTok` (built at `:45`). They render in English in the Arabic UI. Some come from the earlier ticket-form work too, so they're also listed project-wide.
- Blade escapes all user input (`{{ }}`); no `{!! !!}` was added. Admin social links use `rel="noopener noreferrer"` and URLs are restricted to http/https at validation.

## 7. Controller / Service Findings

### INV-C02 — Emails sent inside the DB transaction while holding a row lock (Medium, Reliability)
`Mail::to(...)->send(...)` runs inside `DB::transaction` at `app/Http/Controllers/Admin/InvitationRequestController.php:60` (reject) and `:93` (approve), after `lockForUpdate()` at `:53`.
- If the email is sent and the commit then fails (deadlock, lost connection), the guest receives a QR code for a ticket that doesn't exist, or a rejection for a request that is still Pending.
- A slow SMTP server holds the `invitation_requests` row lock for the whole round-trip.
- **Fix:** commit first with the request marked, then send via a queued mailable with `->afterCommit()` so delivery retries on failure. This keeps the good property the implementation has today (no ticket stays active without the guest being told) without the phantom-email risk.

### INV-C03 — Ticket issuance duplicated a third time (Low, Maintainability)
Ticket-number format, QR secret, workshop key and `TicketIssued` email are re-implemented at `app/Http/Controllers/Admin/InvitationRequestController.php:80-93`. The same steps already exist in `app/Http/Controllers/TicketPaymentController.php:26-39` and `app/Http/Controllers/TicketRequestController.php:124-128`. A change to the reference format or issuance rules now has to be made in three places. **Fix:** a `TicketIssuer` service (`issue(Ticket): void`) used by both paths.

### INV-C07 — Approval doesn't re-check that the ticket type is still active (Informational)
Generation requires an active type (`app/Http/Requests/Admin/InvitationStoreRequest.php:24-27`), but approval (`InvitationRequestController.php:66-79`) doesn't, so an invitation created before a tier was switched off still issues that tier. This is probably acceptable (the invitation was already promised); it's listed so it's a conscious decision.

## 8. Eloquent / Database Findings

### INV-C06 — Deleting a ticket type erases its invitations and their review history (Low, Data integrity)
`database/migrations/2026_09_26_150004_create_invitations_table.php:19` cascades on ticket-type delete, and `…150005_create_invitation_requests_table.php:18` cascades on invitation delete. `app/Http/Controllers/Admin/TicketTypeController.php:61` deletes with no guard. The same pattern also deletes paid tickets project-wide (CR-02 in the root report). **Fix:** `restrictOnDelete()`, or block deleting a ticket type that has tickets or invitations and offer "deactivate" instead.

Good choices: unique `token`; unique `invitation_id` (single use); unique nullable `ticket_id`; `status` indexed; `unsignedInteger` followers bounded at validation.

## 9. Validation / Forms Findings

- Server validation covers every field; the browser `required`/`type` attributes are only a convenience.
- `influencer_category_id` uses `Rule::in([...event category ids, 'other'])`, so another event's category is rejected (tested).
- `name` regex matches the ticket form; phone uses `Phone::international()`.
- `old()` values are restored on error, including the Alpine-driven "Other" reveal (`@js(old(...))`).

## 10. Auth Architecture Findings

Admin routes sit behind `auth` only; as in the rest of the app, any admin user can generate, revoke, approve and reject (project-wide finding). Every nested admin action checks that the model belongs to the `{event}` in the URL (`Admin/InvitationController.php:44-46`, `Admin/InvitationRequestController.php` updateStatus start). The public flow needs the secret token, a valid OTP and a matching session entry, and re-checks usability on every step.

## 11. Queue / Event / Scheduler Findings

No jobs, events or scheduled tasks were added. Emails are sent synchronously in the request (INV-C02). Expired invitations aren't cleaned up; their status stays `unused` and "Expired" is computed on read, as designed.

## 12. Dead & Unused Code

None introduced. `InvitationStatus::label()` (commit 2) is used by `resources/views/admin/invitations/index.blade.php`. The `Invitation::invitationRequest()` relation is unused so far but is a natural inverse; keep it.

## 13. Duplicate / Redundant Code

INV-C03 (ticket issuance). The status-label pattern `__(ucfirst($value))` is still used for invitation *request* statuses (`resources/views/admin/invitation-requests/index.blade.php:36`) while invitation statuses now use an enum `label()`. That's inconsistent but harmless.

## 14. Performance Findings

Measured locally (`tinker`, dev DB): `admin/events/ccs-2026/invitations` = 7 queries / 28 ms; `…/invitation-requests?status=all` = 6 queries / 23 ms. Both eager-load their relations (`with('ticketType')`, `with(['invitation.ticketType','influencerCategory'])`), so there's no N+1. Both lists are unpaginated. That's fine for invitation volumes, but it's the same project-wide pattern.

## 15. Rewrite / Refactoring Opportunities

| Item | Current | Problem | Proposed | Benefit | Effort | Priority |
|---|---|---|---|---|---|---|
| Ticket issuance | 3 copies | Drift risk | `TicketIssuer` service | One place for ref format, QR, key, email | S | DO SOON |
| Review email timing | Mail inside txn | Phantom email, lock held | Queued mailable `afterCommit` | Retryable, no lock over SMTP | S | DO SOON |

## 16. Testing & Coverage

17 methods, 101 assertions, all green. They cover more scenarios than the plan's 28 methods listed: 429 rate limit, cross-event token, mail-failure rollback, double-approve, `javascript:` URL. **Gaps:**
- no report/revenue test for invitation tickets (would have caught INV-C01);
- no test that an invitation-issued ticket passes check-in;
- no test for submitting when the invitation expires between OTP and submit (the code handles it via the in-transaction `isUsable()` re-check);
- no Arabic render test for the new screens.

## 17. Dependency Findings

None added.

## 18. What Is Done Well

- Row locks plus state re-checks on both the submission and the review (improvements over the plan).
- Mail failure rolls back the ticket, so no orphan tickets are left behind.
- A uniform invalid-link page doesn't leak whether a token exists.
- The OTP limiter is keyed by IP and token.
- `RuntimeException` is used instead of `assert()`, so the guard holds in production.
- Consistent house style: `declare(strict_types=1)`, typed signatures, FormRequests, event scoping, factories.
- Commit 2 caught and fixed a real translation-key collision (`"Used"`).

## 19. Production Readiness

**YES WITH MINOR ISSUES** — fix INV-C01 before trusting revenue figures.

## 20. Prioritized Remediation Roadmap

1. INV-C01 revenue (XS–S).
2. INV-C02 after-commit mail (S).
3. INV-C03 `TicketIssuer` (S).
4. INV-C04 Arabic strings (XS).
5. INV-C05 dismissible banner (XS).
6. INV-C06 restrict ticket-type deletion (S, project-wide).

## 21. Coverage & Limitations

Every file in both commits was read. Tests and the Pint check were run locally; query counts were measured against the dev MySQL database with read-only requests. Not done: browser/E2E runs, SMTP delivery, multi-process concurrency tests (locking was verified statically plus the sequential double-approve test).

## SOLID Assessment

| Area | Principle | Status | Evidence | Impact | Recommendation |
|---|---|---|---|---|---|
| `Admin\InvitationRequestController@updateStatus` | S | NEEDS IMPROVEMENT | `:41-113` validates, locks, creates the ticket, issues the key/QR and sends mail | Hard to reuse issuance; 3rd copy | Extract `TicketIssuer` |
| `InvitationController` / `InvitationRequestController` | S | PASS | small, single-purpose | — | — |
| Services use | D | NEEDS IMPROVEMENT | `new WorkshopBooker`, `new TicketQrCode` inside the controller (`:85,87`) | Can't be swapped in tests; matches existing code | Constructor-inject when extracting `TicketIssuer` |
| Enums | O | PASS | `label()` on the enum instead of view branching | — | — |

## ACID / Transaction Integrity Assessment

| Workflow | Atomicity | Consistency | Isolation | Durability | Status | Evidence |
|---|---|---|---|---|---|---|
| Submit request | ✅ txn | ✅ unique `invitation_id` | ✅ `lockForUpdate` + re-check | email after commit ✅ | PASS | `InvitationRequestController.php:40-68` |
| Approve | ✅ ticket + status in one txn | ✅ unique `ticket_id` | ✅ lock + Pending check | ⚠ email inside txn | ISSUE (INV-C02) | `Admin/InvitationRequestController.php:52-97` |
| Reject | ✅ | ✅ | ✅ | ⚠ email inside txn | ISSUE (INV-C02) | `:59-63` |
| Revoke | ✅ single UPDATE | ✅ | ✅ conditional UPDATE | ✅ | PASS | `Admin/InvitationController.php:48-50` |
| Revenue reporting | — | ✗ invariant "invited = 0 revenue" broken | — | — | ISSUE (INV-C01) | `EventReport.php:75` |

## Design Pattern Assessment

| Area | Current Pattern | Problem | Recommended Pattern/Approach | Benefit | Cost | Priority |
|---|---|---|---|---|---|---|
| Ticket issuance | Inline in 2 controllers | Duplication (INV-C03) | Application service (`TicketIssuer`) | Single source of truth | S | DO SOON |
| Post-review emails | Synchronous in txn | INV-C02 | After-commit queued mailable (outbox-lite) | Retry + no phantom mail | S | DO SOON |
| Invitation status | Enum + `label()` | — | APPROPRIATE AS-IS | — | — | — |
| Single-use | Unique FK + row lock | — | APPROPRIATE AS-IS | — | — | — |

## Engineering Improvements

| Improvement | Type | Value | Effort | Priority | Rationale |
|---|---|---|---|---|---|
| Zero-revenue invitation tickets + test | Correctness | High | XS | DO NOW | Reports are wrong today |
| After-commit mail | Reliability | Medium | S | DO SOON | Removes phantom-email risk |
| `TicketIssuer` service | Maintainability | Medium | S | DO SOON | 3 copies of issuance logic |
| Arabic strings | UX | Medium | XS | DO NOW | Bilingual product requirement |
| Dismissible banner | UX | Low | XS | OPTIONAL | Cosmetic |
| Check-in test for invitation tickets | Testability | Medium | XS | DO SOON | Guards the most important downstream path |

## Appendix — Commands Executed

```
git show --stat HEAD~1 HEAD ; git show 4f3ff4c ; git show 1d00644
php artisan test --compact tests/Feature/Admin/InvitationCrudTest.php tests/Feature/Admin/InvitationRequestQueueTest.php tests/Feature/InvitationRequestSubmissionTest.php tests/Feature/InvitationVerificationTest.php tests/Unit/Models/InvitationTest.php   # 17 passed
php artisan test --compact     # 660 passed
vendor/bin/pint --test --format agent   # passed
php artisan migrate:status     # invitation tables Ran
php artisan tinker (read-only GET requests with query log) for admin invitation pages
python lang key diff (en vs ar, invitation view keys)
```

Source code was not modified.

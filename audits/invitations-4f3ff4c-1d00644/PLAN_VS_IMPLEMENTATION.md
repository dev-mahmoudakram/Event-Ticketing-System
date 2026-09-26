# Plan vs Implementation — Event Invitations

**Plan:** `docs/superpowers/plans/2026-09-19-event-invitations.md` (spec: `docs/superpowers/specs/2026-09-19-event-invitations-design.md`)
**Implementation:** `4f3ff4c` *feat: add event invitation requests and direct ticket issuance* (34 files, +1466/−2) and `1d00644` *fix: remove duplicate translations and type event dates* (5 files, +17/−5)
**Reviewed:** 2026-09-26, read-only. Tests run: 17 invitation tests pass (101 assertions); full suite 660 pass; Pint clean; both invitation migrations show `Ran` on the dev DB.

## Verdict

**The implementation delivers every task in the plan and is better than the plan in the places that matter most** — concurrency, single-use enforcement, and failure handling. Every deviation found is either an improvement or cosmetic, with one exception that the plan *and* spec share: invitation tickets are recorded at the ticket type's full price with `is_paid = true`, so they are counted as collected revenue in every report (see finding INV-C01 in `CODE_REVIEW_REPORT.md`). That defect was designed in, not introduced by the implementer.

| | Count |
|---|---|
| Plan tasks fully delivered | 6 / 6 |
| Deviations that improve on the plan | 11 |
| Cosmetic / neutral deviations | 8 |
| Deviations that regress on the plan | 1 (approval email moved inside the DB transaction — a trade-off, see §3) |
| Defects shared by plan and implementation | 1 (revenue inflation) |
| Items not in the plan that were added | translations (en/ar), enum `label()`, landing-page confirmation banner, stricter validation |

---

## 1. Task-by-task

| Plan task | Delivered? | Where | Notes |
|---|---|---|---|
| 1. Data model (enums, 2 migrations, 2 models, factories, `Event` relations, model test) | ✅ Yes | `app/Enums/Invitation*.php`, `database/migrations/2026_09_26_15000{4,5}_*.php`, `app/Models/Invitation*.php`, `app/Models/Event.php:208-216` | Adds a unique FK on `invitation_requests.ticket_id` (not in plan) — prevents one ticket being linked to two requests. |
| 2. Admin generate / list / revoke | ✅ Yes | `app/Http/Controllers/Admin/InvitationController.php`, `resources/views/admin/invitations/index.blade.php` | Only **active** ticket types are offered and accepted (plan allowed any). Revoke is an atomic conditional `UPDATE` instead of read-then-write. |
| 3. Public link + OTP verification | ✅ Yes | `app/Http/Controllers/InvitationController.php`, `resources/views/invitations/verify.blade.php` | Rate limit is a named limiter keyed `ip|token` (plan: generic `throttle:5,1`). OTP validated as `digits:6` (plan: `string`). |
| 4. Public request form | ✅ Yes | `app/Http/Controllers/InvitationRequestController.php`, `app/Http/Requests/InvitationRequestStoreRequest.php`, `resources/views/invitations/create.blade.php` | Submission locks the invitation row and re-checks `isUsable()` inside the transaction (plan: no lock). |
| 5. Admin review queue (approve → ticket, reject → email) | ✅ Yes | `app/Http/Controllers/Admin/InvitationRequestController.php`, `resources/views/admin/invitation-requests/index.blade.php`, `app/Mail/InvitationRequestRejected.php` | Locks the request row and refuses anything not `Pending` (plan had no such guard). Email sending moved **inside** the transaction. |
| 6. Full-suite verification + dev DB migration | ✅ Yes | — | 660 tests pass; `php artisan migrate:status` shows both tables `Ran` (batch 26). |

## 2. Deviations that improve on the plan

| # | Plan said | Implementation does | Why it's better | Evidence |
|---|---|---|---|---|
| I-1 | Approve/reject with no status check | Row lock + `status !== Pending → return false` | Double-click or two admins can no longer issue two tickets or reject an approved request. Tested: a second approve PATCH creates no second ticket and sends no second email. | `app/Http/Controllers/Admin/InvitationRequestController.php:52-57`; `tests/Feature/Admin/InvitationRequestQueueTest.php:46-48` |
| I-2 | On approve: create ticket in a transaction, send email after it, then mark Approved | Everything in one transaction; if the email throws, the ticket is rolled back and the request stays Pending | The plan's version could leave a ticket created but the request still Pending (and a retry would create a second ticket). | `InvitationRequestController.php:52-97`; test `test_mail_failure_rolls_back_ticket_and_leaves_request_pending` |
| I-3 | Submit: check session + `isUsable()`, then insert | Same, plus `lockForUpdate()` and a re-check inside the transaction | Two concurrent submissions against one invitation can't both pass the check (the unique FK would also stop the second, but now it fails cleanly instead of with a DB error). | `app/Http/Controllers/InvitationRequestController.php:40-45` |
| I-4 | Revoke: load, check Unused, update | Single `UPDATE … WHERE status='unused'` | No read-then-write race with a concurrent submission. | `app/Http/Controllers/Admin/InvitationController.php:48-50` |
| I-5 | `throttle:5,1` (per IP) | Named limiter `invitation-otp`, 5/min keyed by `ip|token` | One visitor guessing on one link doesn't lock the same IP out of a different link. | `app/Providers/AppServiceProvider.php:31-32` |
| I-6 | `otp` rule `required, string` | `required, digits:6` | Rejects junk before any comparison. | `app/Http/Controllers/InvitationController.php:32` |
| I-7 | Social URLs `url` | `url:http,https` | `javascript:` and other schemes rejected; covered by a test. | `app/Http/Requests/InvitationRequestStoreRequest.php:33-38` |
| I-8 | Followers `integer, min:0` | `integer, between:0,4294967295` | Matches the `unsignedInteger` column, so an overflow can't reach the DB. | same file |
| I-9 | Ticket types: any in the event | Only `is_active` ticket types, in list and validation | Admins can't hand out an invitation to a retired tier. | `app/Http/Requests/Admin/InvitationStoreRequest.php:24-27` |
| I-10 | `invitation_requests.ticket_id` nullable FK | Nullable **unique** FK, `phone` length 32, `status` length 20 | Tighter schema; one ticket per request enforced by the DB. | `database/migrations/2026_09_26_150005_create_invitation_requests_table.php:22,31,32` |
| I-11 | Status shown as `ucfirst($value)` | `InvitationStatus::label()` with translated labels (commit `1d00644`) | Fixes an Arabic label clash on the shared `"Used"` key, which discount coupons already use to mean "times used". | `app/Enums/InvitationStatus.php:13-20` |

## 3. Deviations that are a trade-off

| # | Plan | Implementation | Trade-off | Evidence |
|---|---|---|---|---|
| T-1 | Send `TicketIssued` **after** the DB transaction commits | Sent **inside** the transaction while holding a row lock | Gains atomicity (no ticket without email). Loses: (a) if the commit fails after the email was sent, the guest holds a QR for a ticket that doesn't exist; (b) a slow mail server holds the row lock for the full SMTP round-trip. With `MAIL_MAILER=log` locally this is invisible. The recommended fix is outbox-style dispatch (see INV-C02). | `InvitationRequestController.php:52-97` |

## 4. Cosmetic / neutral deviations

| # | Plan | Implementation |
|---|---|---|
| N-1 | Migration timestamps `2026_09_26_150000/150001` | `…150004/150005` |
| N-2 | `$attributes = ['status' => InvitationStatus::Unused]` | `['status' => 'unused']` (same effect after cast) |
| N-3 | `assert($qrImage !== null)` | `throw new RuntimeException(...)` — better: `assert()` is a no-op when `zend.assertions=-1` in production |
| N-4 | Admin list shows OTP only | Shows OTP **and** a copyable link per row (useful, matches the "view anytime" decision) |
| N-5 | Invalid-link copy: "The link may have expired…" | "Please contact the person who invited you…" — still identical for all four invalid cases, as required |
| N-6 | Form inputs with placeholders | Inputs looped from arrays; name/email placeholders dropped |
| N-7 | 5 commits (one per task) | 1 feature commit + 1 fix commit |
| N-8 | ~28 test methods | 17 methods, 101 assertions — fewer methods but each covers several scenarios; adds tests the plan didn't have (429 rate limit, cross-event token, email-failure rollback, double-approve idempotency, `javascript:` URL rejection) |

## 5. Additions not in the plan

- **Translations** for all new strings in `lang/en.json` and `lang/ar.json` (~40 keys each). Commit `1d00644` removed one genuine duplicate key and renamed `"Used"` → `"Used invitation"` to stop the clash described in I-11. **Seven strings used by the new screens still have no Arabic entry**: `Category`, `Social`, `We received your request`, `We received your request.`, `Influencer Category`, `Other`, `Tell us your category`, plus `TikTok` (built dynamically). They fall back to English in the Arabic UI (INV-C04).
- **Landing-page confirmation banner** after submitting (`resources/views/landing/show.blade.php:20-24`). It has no close button or timeout and stays pinned over the page until the next navigation (INV-C05).
- `declare(strict_types=1);` added to `app/Providers/AppServiceProvider.php`.
- `@property Carbon $start_date/$end_date` docblock on `Event` (commit `1d00644`) — fixes IDE/static-analysis warnings seen elsewhere in the project.

## 6. Global-constraint compliance

| Plan global constraint | Met? | Evidence |
|---|---|---|
| Single-use via unique FK on `invitation_requests.invitation_id` | ✅ | migration `…150005…:18` |
| 7-day expiry | ✅ | `Admin/InvitationController.php:35` |
| OTP stored plain, viewable anytime | ✅ | migration `…150004…:21`; admin list shows it |
| No payment step; ticket created `TicketIssued`, `is_paid=true`, `payment_method='invitation'` | ✅ | `Admin/InvitationRequestController.php:67-79` |
| Reuse `TicketIssued` mail unchanged | ✅ | `Admin/InvitationRequestController.php:93` |
| Fixed field set, not `TicketRequestField` | ✅ | `InvitationRequestStoreRequest.php` |
| "Other" category + `require_influencer_category` honoured | ✅ | `InvitationRequestStoreRequest.php:28-31`; `InvitationRequestController.php:47-55` |
| All three social rows always shown, URL + followers same row | ✅ | `resources/views/invitations/create.blade.php:42-55` |
| Identical page for missing/expired/used/revoked | ✅ | `InvitationController.php:43-48`; test `test_unknown_expired_used_and_revoked_links_show_the_same_invalid_page` |
| OTP rate-limited 5/min | ✅ (improved, I-5) | `AppServiceProvider.php:31-32`; test asserts 429 on the 6th attempt |
| `declare(strict_types=1)` + typed signatures | ✅ | all new PHP files |
| Pint | ✅ | `vendor/bin/pint --test` → passed |
| Migrate dev DB | ✅ | `migrate:status` → `Ran` |

## 7. What the plan got wrong (and the implementation inherited)

1. **Revenue inflation (High).** Spec §"Approve/reject" and plan Task 5 both set `price` = the ticket type's price and `is_paid = true`. Every revenue query in `app/Services/EventReport.php:75,94` and `app/Services/PlatformReport.php:43,66,171` sums `price − discount_amount` over `is_paid` tickets, so each invited guest shows up as a full-price sale. Fix: record `discount_amount = price` (net 0) — this keeps the ticket type's list price visible for reporting while making "collected" correct — or exclude `payment_method = 'invitation'` from revenue sums. Add a report test with one invitation ticket.
2. **Cascade deletes.** The plan specified `ticket_type_id … cascadeOnDelete()` on `invitations`, copying the existing `tickets` table. Deleting a ticket type therefore deletes its invitations and, through `invitation_id … cascadeOnDelete()`, their requests — erasing the review history. This is the same underlying project-wide problem reported as CR-02 (deleting a ticket type deletes paid tickets).
3. **Email inside vs after the transaction.** Neither the plan's ordering nor the implementation's is fully safe; see T-1.

## 8. Recommended follow-ups (in order)

1. Fix revenue accounting for invitation tickets and add a report test (INV-C01).
2. Move `TicketIssued`/`InvitationRequestRejected` sending to `afterCommit` (queued mailable with `->afterCommit()`), or mark the request `approved` and dispatch mail after commit with a retryable job (INV-C02).
3. Extract a `TicketIssuer` service used by both `TicketPaymentController` and the invitation approval, so ticket number, QR secret, workshop key and email live in one place (INV-C03 / CR-10).
4. Add the eight missing Arabic strings (INV-C04).
5. Make the landing confirmation banner dismissible (INV-C05).

# Event Policy Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Per-event, admin-editable policy pages (Terms, Privacy, Refund & Cancellation, Delivery, About, Contact, plus any custom page) linked from the event footer, the ticket request form and the approval email, so an Egyptian payment gateway can approve the site.

**Architecture:** One `event_pages` table; six required pages per event are seeded with starter drafts rendered from Blade templates by a small `PolicyDrafts` support class (pure input → HTML, so the migration can use it without Eloquent). An admin CRUD under a new `Permission::Pages`; a public `event-pages.show` route; the event footer gains a Policies column and card logos; the ticket request form gains a required `accept_terms` box recorded as `terms_accepted_at`.

**Tech Stack:** Laravel 12, PHP 8.3, MySQL + SQLite tests, Blade/Alpine/Tailwind, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-01-event-policy-pages-design.md`

## Global Constraints

- No new Composer/npm dependencies; card logos are inline SVG.
- Event palette only (coral, gold, maroon, red, black, white/grey) — the no-teal test applies.
- Every user-visible string through `__()` with Arabic in `lang/ar.json`; draft page bodies are per-locale Blade files (not `__()`).
- Required page keys/slugs exactly: `terms`→`terms`, `privacy`→`privacy-policy`, `refund`→`refund-policy`, `delivery`→`delivery-policy`, `about`→`about`, `contact`→`contact`.
- Required pages: not deletable, slug and key fixed, always published.
- Rich text through the existing `App\Casts\SanitizedRichText` cast.
- Every new admin route maps to `Permission::Pages` (route-coverage test).
- Run `vendor/bin/pint --dirty --format agent`; tests `php artisan test --compact`.
- Commits under the user's identity, no `Co-Authored-By`.
- Heredocs strip backslashes in this shell — write files with the file tool.

## Review Focus

1. **An event with the newsletter section switched off** must still show the Policies column and card logos — the footer is currently wrapped entirely in the newsletter check. Task 3 test `test_policies_show_even_when_the_newsletter_is_off`.
2. **Sending the Contact page form** must come back to the Contact page with the success message, not jump to the landing page. Task 3 test `test_the_contact_page_form_returns_to_the_contact_page`.
3. **A tampered request trying to unpublish or re-slug a required page** must leave it published at its fixed address. Task 2 test `test_a_required_page_stays_published_at_its_address`.
4. **Re-running the seeder or creating pages twice** must not duplicate required pages (unique key per event). Task 1 test `test_seeding_required_pages_twice_creates_them_once`.
5. **A page body with an Arabic placeholder only** (English filled in) must still be flagged "Needs your details". Task 1 test `test_a_placeholder_in_either_language_flags_the_page`.

---

## File Structure

| File | Responsibility |
|---|---|
| `app/Enums/RequiredPage.php` (new) | The six required pages: key, slug, titles, sort order |
| `app/Support/PolicyDrafts.php` (new) | Renders a draft body for a page key + locale from plain event data |
| `resources/views/event-pages/drafts/{key}-{ar,en}.blade.php` (new, 12) | Starter draft text |
| `database/migrations/*_create_event_pages_table.php` (new) | Table + required pages for existing events |
| `app/Models/EventPage.php` + factory (new) | Model; `seedRequiredFor()`, `needsDetails()`, `isRequired()`, `title()`, `body()` |
| `app/Models/Event.php` | `pages()` relation; created hook seeds required pages |
| `database/seeders/CcsEventSeeder.php` | Seeds required pages when model events are off |
| `app/Enums/Permission.php` | New `Pages` case |
| `app/Http/Controllers/Admin/EventPageController.php`, `app/Http/Requests/Admin/EventPageRequest.php`, `resources/views/admin/event-pages/{index,form}.blade.php` (new) | Admin CRUD + reorder |
| `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php` | Routes and sidebar link |
| `app/Http/Controllers/EventPageController.php`, `resources/views/event-pages/show.blade.php` (new) | Public page |
| `resources/views/landing/partials/contact-form.blade.php` (new, extracted) , `contact.blade.php` | Shared contact form |
| `app/Http/Controllers/ContactMessageController.php` | `return_to=contact-page` redirect |
| `resources/views/landing/partials/footer.blade.php`, `resources/views/components/payment-logos.blade.php` (new) | Policies column, logos, footer no longer hidden with the newsletter |
| `database/migrations/*_add_terms_accepted_at_to_tickets_table.php` (new), `app/Models/Ticket.php`, `app/Http/Requests/TicketRequestStoreRequest.php`, `app/Http/Controllers/TicketRequestController.php`, `resources/views/landing/partials/ticket-request-modal.blade.php` | Terms agreement |
| `resources/views/emails/ticket-requests/approved.blade.php` | Policy line |

---

### Task 1: Pages model, required set and starter drafts

**Files:**
- Create: `app/Enums/RequiredPage.php`, `app/Support/PolicyDrafts.php`, the 12 draft views, migration `create_event_pages_table`, `app/Models/EventPage.php`, `database/factories/EventPageFactory.php`
- Modify: `app/Models/Event.php`, `database/seeders/CcsEventSeeder.php`
- Test: `tests/Feature/EventPagesModelTest.php` (new)

**Interfaces:**
- Produces: `enum RequiredPage: string` (cases `Terms='terms'`, `Privacy='privacy'`, `Refund='refund'`, `Delivery='delivery'`, `About='about'`, `Contact='contact'`; methods `slug(): string`, `titleEn(): string`, `titleAr(): string`, `sortOrder(): int`).
- Produces: `PolicyDrafts::render(RequiredPage $page, string $locale, array $event): string` where `$event` = `['name' => string, 'email' => ?string, 'phone' => ?string, 'venue' => ?string, 'address' => ?string, 'currency' => string]`; `PolicyDrafts::eventData(Event $event, string $locale): array`.
- Produces: `EventPage` (fillable `event_id, key, slug, title_ar, title_en, body_ar, body_en, show_in_footer, is_published, sort_order`; casts bodies `SanitizedRichText`, booleans; `requiredPage(): ?RequiredPage`, `isRequired(): bool`, `title(): string`, `body(): ?string`, `needsDetails(): bool`, `static seedRequiredFor(Event $event): void`, const `PLACEHOLDER_PATTERN = '/\[[^\]\n]{2,80}\]/u'`); `Event::pages(): HasMany` ordered by `sort_order`, `id`.

- [ ] **Step 1: Write the failing tests** — `tests/Feature/EventPagesModelTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequiredPage;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPagesModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_event_gets_the_six_required_pages(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(
            ['terms', 'privacy-policy', 'refund-policy', 'delivery-policy', 'about', 'contact'],
            $event->pages()->pluck('slug')->all(),
        );
        $this->assertTrue($event->pages()->get()->every(fn (EventPage $page) => $page->isRequired() && $page->is_published));
    }

    public function test_seeding_required_pages_twice_creates_them_once(): void
    {
        $event = Event::factory()->create();

        EventPage::seedRequiredFor($event);

        $this->assertSame(6, $event->pages()->count());
    }

    public function test_drafts_are_filled_from_the_event(): void
    {
        $event = Event::factory()->create([
            'name_en' => 'Content Creators Summit', 'name_ar' => 'قمة صناع المحتوى',
            'contact_email' => 'hello@ccs.test', 'contact_phone' => '+20 100 000 0000',
        ]);
        TicketType::factory()->for($event)->create(['currency' => 'EGP']);
        $event->pages()->delete();
        EventPage::seedRequiredFor($event->fresh());

        $refund = $event->pages()->where('key', RequiredPage::Refund->value)->sole();
        $this->assertStringContainsString('Content Creators Summit', $refund->body_en);
        $this->assertStringContainsString('hello@ccs.test', $refund->body_en);
        $this->assertStringContainsString('EGP', $refund->body_en);
        $this->assertStringContainsString('قمة صناع المحتوى', $refund->body_ar);
        $this->assertTrue($refund->needsDetails());
    }

    public function test_a_page_without_placeholders_does_not_need_details(): void
    {
        $page = EventPage::factory()->create(['body_en' => '<p>Done.</p>', 'body_ar' => '<p>تم.</p>']);

        $this->assertFalse($page->needsDetails());
    }

    public function test_a_placeholder_in_either_language_flags_the_page(): void
    {
        $page = EventPage::factory()->create(['body_en' => '<p>Done.</p>', 'body_ar' => '<p>[اسم الشركة القانوني]</p>']);

        $this->assertTrue($page->needsDetails());
    }

    public function test_required_pages_know_their_place(): void
    {
        $this->assertSame('refund-policy', RequiredPage::Refund->slug());
        $this->assertSame('Refund & Cancellation Policy', RequiredPage::Refund->titleEn());
        $this->assertSame(6, count(RequiredPage::cases()));
    }
}
```

- [ ] **Step 2: Run to verify failure** — `php artisan test --compact tests/Feature/EventPagesModelTest.php` → FAIL (`Class "App\Enums\RequiredPage" not found`).

- [ ] **Step 3: RequiredPage enum** — `app/Enums/RequiredPage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The pages a payment gateway checks before approving the site. Every event has all six; they
 * can be edited but never deleted, renamed to another address or unpublished.
 */
enum RequiredPage: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Refund = 'refund';
    case Delivery = 'delivery';
    case About = 'about';
    case Contact = 'contact';

    public function slug(): string
    {
        return match ($this) {
            self::Terms => 'terms',
            self::Privacy => 'privacy-policy',
            self::Refund => 'refund-policy',
            self::Delivery => 'delivery-policy',
            self::About => 'about',
            self::Contact => 'contact',
        };
    }

    public function titleEn(): string
    {
        return match ($this) {
            self::Terms => 'Terms & Conditions',
            self::Privacy => 'Privacy Policy',
            self::Refund => 'Refund & Cancellation Policy',
            self::Delivery => 'Ticket Delivery Policy',
            self::About => 'About Us',
            self::Contact => 'Contact Us',
        };
    }

    public function titleAr(): string
    {
        return match ($this) {
            self::Terms => 'الشروط والأحكام',
            self::Privacy => 'سياسة الخصوصية',
            self::Refund => 'سياسة الاسترداد والإلغاء',
            self::Delivery => 'سياسة تسليم التذاكر',
            self::About => 'من نحن',
            self::Contact => 'تواصل معنا',
        };
    }

    public function sortOrder(): int
    {
        return array_search($this, self::cases(), true);
    }
}
```

- [ ] **Step 4: PolicyDrafts** — `app/Support/PolicyDrafts.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RequiredPage;
use App\Models\Event;

/**
 * Starter text for the required pages, filled from what the event already knows. Anything only
 * the organiser knows stays as a [bracketed placeholder] for them to replace. Takes plain data so
 * the migration can use it without loading models.
 */
final class PolicyDrafts
{
    /**
     * @param  array{name: string, email: ?string, phone: ?string, venue: ?string, address: ?string, currency: string}  $event
     */
    public static function render(RequiredPage $page, string $locale, array $event): string
    {
        return trim(view("event-pages.drafts.{$page->value}-{$locale}", ['event' => $event])->render());
    }

    /**
     * @return array{name: string, email: ?string, phone: ?string, venue: ?string, address: ?string, currency: string}
     */
    public static function eventData(Event $event, string $locale): array
    {
        $ar = $locale === 'ar';

        return [
            'name' => $ar ? $event->name_ar : $event->name_en,
            'email' => $event->contact_email,
            'phone' => $event->contact_phone,
            'venue' => $ar ? $event->venue_name_ar : $event->venue_name_en,
            'address' => $ar ? $event->venue_address_ar : $event->venue_address_en,
            'currency' => (string) ($event->ticketTypes()->value('currency') ?: 'EGP'),
        ];
    }
}
```

- [ ] **Step 5: Draft views** — create the 12 files. Each uses `$event['name']`, `$event['email'] ?? '[…]'` etc. English set (`resources/views/event-pages/drafts/*-en.blade.php`):

`terms-en.blade.php`:

```blade
<h2>About these terms</h2>
<p>These terms apply to tickets for {{ $event['name'] }}, organised by [Company legal name] (Commercial Registration [Commercial registration number]). By requesting or buying a ticket you agree to them.</p>
<h2>How tickets work</h2>
<p>Tickets are requested on this website and reviewed by our team. If a request is approved, we email a payment link. Once payment is confirmed, the e-ticket — with its QR code and workshop booking key — is emailed to the address on the request.</p>
<ul>
<li>Each ticket admits one named person and cannot be transferred without our written agreement.</li>
<li>Prices are shown in {{ $event['currency'] }} and include any applicable taxes unless stated otherwise.</li>
<li>Entry requires the QR code on the e-ticket, which is checked once at the door.</li>
<li>Workshop places are limited and booked with the workshop booking key, first come, first served.</li>
</ul>
<h2>At the event</h2>
<p>We may refuse entry or remove anyone whose behaviour puts others at risk or breaks venue rules. The programme, speakers and timings may change; we will tell ticket holders about significant changes by email.</p>
<h2>Refunds</h2>
<p>Refunds and cancellations follow our Refund &amp; Cancellation Policy.</p>
<h2>Liability</h2>
<p>To the extent allowed by law, [Company legal name] is not liable for indirect losses, or for loss of or damage to personal belongings at the venue.</p>
<h2>Governing law</h2>
<p>These terms are governed by the laws of the Arab Republic of Egypt.</p>
<h2>Contact</h2>
<p>Questions about these terms: {{ $event['email'] ?? '[Contact email]' }}{{ $event['phone'] ? ' · '.$event['phone'] : '' }}.</p>
```

`privacy-en.blade.php`:

```blade
<h2>Who we are</h2>
<p>[Company legal name] organises {{ $event['name'] }} and is responsible for the personal data described here.</p>
<h2>What we collect</h2>
<ul>
<li>Your name, email address and phone number.</li>
<li>Information you add to a request: social media links, follower counts, portfolio links and uploaded files.</li>
<li>Your ticket, workshop bookings and check-in time.</li>
</ul>
<h2>Why we use it</h2>
<p>To review ticket requests, issue and deliver tickets, check you in at the event, and send you updates about the event. We do not sell your data or share it for marketing.</p>
<h2>Payments</h2>
<p>Card payments are processed by our payment provider. Your card details are entered on their secure page and are never stored on this website.</p>
<h2>How long we keep it</h2>
<p>We keep your data for [Retention period, e.g. 24 months] after the event, then delete it, unless the law requires us to keep it longer.</p>
<h2>Your rights</h2>
<p>You can ask to see, correct or delete your data at any time by emailing {{ $event['email'] ?? '[Contact email]' }}.</p>
<h2>Cookies</h2>
<p>This website uses only the cookies it needs to work, such as keeping your language choice and securing forms.</p>
```

`refund-en.blade.php`:

```blade
<h2>Before payment</h2>
<p>A ticket request costs nothing. You can withdraw a pending or approved request at any time before paying by emailing {{ $event['email'] ?? '[Contact email]' }} with your reference number.</p>
<h2>After payment</h2>
<ul>
<li>Paid tickets can be refunded if you ask [Refund window, e.g. at least 7 days before the event].</li>
<li>Refunds are paid back to the card used, through our payment provider, within [Refund processing time, e.g. 14 business days]. Amounts are refunded in {{ $event['currency'] }}.</li>
<li>Tickets are not refundable after that window, or once the ticket has been used to enter the event.</li>
</ul>
<h2>If the event changes</h2>
<p>If {{ $event['name'] }} is cancelled, every paid ticket is refunded in full. If the date or venue changes, your ticket stays valid, and you can ask for a refund within [Change refund window, e.g. 7 days] of our announcement.</p>
<h2>How to ask for a refund</h2>
<p>Email {{ $event['email'] ?? '[Contact email]' }}{{ $event['phone'] ? ' or call '.$event['phone'] : '' }} with your name and reference number.</p>
```

`delivery-en.blade.php`:

```blade
<h2>Digital tickets only</h2>
<p>Tickets for {{ $event['name'] }} are electronic. Nothing is shipped.</p>
<h2>When you receive your ticket</h2>
<p>As soon as your payment is confirmed, your e-ticket is emailed to the address on your request. It includes a QR code for entry and a workshop booking key, and a link to view the ticket online.</p>
<h2>If it doesn't arrive</h2>
<p>Check your spam or promotions folder first. If it still isn't there within [Delivery support time, e.g. 1 hour], email {{ $event['email'] ?? '[Contact email]' }} with your reference number and we will resend it.</p>
```

`about-en.blade.php`:

```blade
<h2>{{ $event['name'] }}</h2>
<p>[A short description of the event: who it is for and what happens there.]</p>
<h2>The organiser</h2>
<p>{{ $event['name'] }} is organised by [Company legal name], Commercial Registration [Commercial registration number], Tax ID [Tax ID], [Business address].</p>
@if($event['venue'])
<h2>Where</h2>
<p>{{ $event['venue'] }}{{ $event['address'] ? ', '.$event['address'] : '' }}</p>
@endif
```

`contact-en.blade.php`:

```blade
<p>We're happy to help with tickets, payments, workshops or anything else about {{ $event['name'] }}. Send us a message below, or reach us directly.</p>
```

Arabic set (`*-ar.blade.php`) — same structure, Arabic text, Arabic placeholders:

`terms-ar.blade.php`:

```blade
<h2>عن هذه الشروط</h2>
<p>تنطبق هذه الشروط على تذاكر {{ $event['name'] }}، التي تنظمها [الاسم القانوني للشركة] (سجل تجاري رقم [رقم السجل التجاري]). بطلبك أو شرائك تذكرة فأنت توافق عليها.</p>
<h2>كيف تعمل التذاكر</h2>
<p>تُطلب التذاكر عبر هذا الموقع ويراجعها فريقنا. عند الموافقة نرسل رابط الدفع بالبريد الإلكتروني، وبعد تأكيد الدفع تُرسل التذكرة الإلكترونية — مع رمز QR ومفتاح حجز ورش العمل — إلى البريد المسجّل في الطلب.</p>
<ul>
<li>كل تذكرة تخص شخصًا واحدًا باسمه ولا يجوز نقلها دون موافقتنا الكتابية.</li>
<li>الأسعار معروضة بعملة {{ $event['currency'] }} وتشمل الضرائب المطبقة ما لم يُذكر غير ذلك.</li>
<li>يتطلب الدخول رمز QR الموجود على التذكرة، ويُفحص مرة واحدة عند الباب.</li>
<li>أماكن ورش العمل محدودة وتُحجز بمفتاح الحجز حسب الأسبقية.</li>
</ul>
<h2>أثناء الفعالية</h2>
<p>يحق لنا رفض دخول أو إخراج أي شخص يعرّض الآخرين للخطر أو يخالف قواعد المكان. قد يتغير البرنامج أو المتحدثون أو المواعيد، وسنبلغ حاملي التذاكر بالتغييرات المهمة عبر البريد الإلكتروني.</p>
<h2>الاسترداد</h2>
<p>يخضع الاسترداد والإلغاء لسياسة الاسترداد والإلغاء الخاصة بنا.</p>
<h2>المسؤولية</h2>
<p>في حدود ما يسمح به القانون، لا تتحمل [الاسم القانوني للشركة] مسؤولية الخسائر غير المباشرة أو فقدان المتعلقات الشخصية أو تلفها في مكان الفعالية.</p>
<h2>القانون المطبق</h2>
<p>تخضع هذه الشروط لقوانين جمهورية مصر العربية.</p>
<h2>التواصل</h2>
<p>للاستفسار عن هذه الشروط: {{ $event['email'] ?? '[البريد الإلكتروني للتواصل]' }}{{ $event['phone'] ? ' · '.$event['phone'] : '' }}.</p>
```

`privacy-ar.blade.php`:

```blade
<h2>من نحن</h2>
<p>تنظم [الاسم القانوني للشركة] فعالية {{ $event['name'] }} وهي المسؤولة عن البيانات الشخصية الموضحة هنا.</p>
<h2>ما الذي نجمعه</h2>
<ul>
<li>اسمك وبريدك الإلكتروني ورقم هاتفك.</li>
<li>ما تضيفه إلى طلبك: روابط حساباتك وعدد المتابعين وروابط أعمالك والملفات المرفوعة.</li>
<li>تذكرتك وحجوزات ورش العمل ووقت تسجيل حضورك.</li>
</ul>
<h2>لماذا نستخدمها</h2>
<p>لمراجعة طلبات التذاكر وإصدارها وتسليمها، وتسجيل حضورك في الفعالية، وإرسال تحديثات عنها. لا نبيع بياناتك ولا نشاركها لأغراض تسويقية.</p>
<h2>المدفوعات</h2>
<p>تتم معالجة الدفع بالبطاقات عبر مزوّد خدمة الدفع لدينا. تُدخل بيانات بطاقتك في صفحته الآمنة ولا تُخزَّن أبدًا على هذا الموقع.</p>
<h2>مدة الاحتفاظ</h2>
<p>نحتفظ ببياناتك لمدة [مدة الاحتفاظ، مثل 24 شهرًا] بعد الفعالية ثم نحذفها، ما لم يُلزمنا القانون بالاحتفاظ بها مدة أطول.</p>
<h2>حقوقك</h2>
<p>يمكنك طلب الاطلاع على بياناتك أو تصحيحها أو حذفها في أي وقت عبر {{ $event['email'] ?? '[البريد الإلكتروني للتواصل]' }}.</p>
<h2>ملفات تعريف الارتباط</h2>
<p>يستخدم هذا الموقع ملفات تعريف الارتباط الضرورية فقط لعمله، مثل حفظ اختيار اللغة وتأمين النماذج.</p>
```

`refund-ar.blade.php`:

```blade
<h2>قبل الدفع</h2>
<p>طلب التذكرة مجاني. يمكنك سحب طلب قيد المراجعة أو تمت الموافقة عليه في أي وقت قبل الدفع بمراسلتنا على {{ $event['email'] ?? '[البريد الإلكتروني للتواصل]' }} مع رقمك المرجعي.</p>
<h2>بعد الدفع</h2>
<ul>
<li>يمكن استرداد قيمة التذاكر المدفوعة إذا طلبت ذلك [مدة الاسترداد، مثل قبل الفعالية بسبعة أيام على الأقل].</li>
<li>تُرد المبالغ إلى البطاقة المستخدمة عبر مزوّد خدمة الدفع خلال [مدة المعالجة، مثل 14 يوم عمل]، وبعملة {{ $event['currency'] }}.</li>
<li>لا تُسترد قيمة التذاكر بعد انتهاء هذه المدة أو بعد استخدام التذكرة لدخول الفعالية.</li>
</ul>
<h2>إذا تغيّرت الفعالية</h2>
<p>إذا أُلغيت {{ $event['name'] }} تُرد قيمة جميع التذاكر المدفوعة كاملة. وإذا تغيّر الموعد أو المكان تظل تذكرتك صالحة، ويمكنك طلب الاسترداد خلال [مدة الاسترداد عند التغيير، مثل 7 أيام] من إعلاننا.</p>
<h2>كيف تطلب الاسترداد</h2>
<p>راسلنا على {{ $event['email'] ?? '[البريد الإلكتروني للتواصل]' }}{{ $event['phone'] ? ' أو اتصل بنا على '.$event['phone'] : '' }} مع اسمك ورقمك المرجعي.</p>
```

`delivery-ar.blade.php`:

```blade
<h2>تذاكر إلكترونية فقط</h2>
<p>تذاكر {{ $event['name'] }} إلكترونية، ولا يُشحن أي شيء.</p>
<h2>متى تصلك التذكرة</h2>
<p>فور تأكيد الدفع تُرسل تذكرتك الإلكترونية إلى البريد المسجّل في طلبك، وتتضمن رمز QR للدخول ومفتاح حجز ورش العمل ورابطًا لعرض التذكرة على الإنترنت.</p>
<h2>إذا لم تصلك</h2>
<p>تحقق أولًا من مجلد الرسائل غير المرغوب فيها أو العروض. وإذا لم تجدها خلال [مدة الدعم، مثل ساعة واحدة] راسلنا على {{ $event['email'] ?? '[البريد الإلكتروني للتواصل]' }} مع رقمك المرجعي وسنعيد إرسالها.</p>
```

`about-ar.blade.php`:

```blade
<h2>{{ $event['name'] }}</h2>
<p>[وصف قصير للفعالية: لمن هي وما الذي يحدث فيها.]</p>
<h2>الجهة المنظمة</h2>
<p>تنظم {{ $event['name'] }} شركة [الاسم القانوني للشركة]، سجل تجاري رقم [رقم السجل التجاري]، رقم ضريبي [الرقم الضريبي]، [عنوان الشركة].</p>
@if($event['venue'])
<h2>المكان</h2>
<p>{{ $event['venue'] }}{{ $event['address'] ? '، '.$event['address'] : '' }}</p>
@endif
```

`contact-ar.blade.php`:

```blade
<p>يسعدنا مساعدتك في التذاكر أو الدفع أو ورش العمل أو أي شيء يخص {{ $event['name'] }}. أرسل لنا رسالة أدناه أو تواصل معنا مباشرة.</p>
```

- [ ] **Step 6: Migration** — `php artisan make:migration create_event_pages_table --no-interaction`:

```php
<?php

use App\Enums\RequiredPage;
use App\Support\PolicyDrafts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-event pages (policies, about, contact, anything custom). Every existing event gets the six
 * pages a payment gateway checks, with starter drafts built from plain event data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('key', 30)->nullable();
            $table->string('slug', 100);
            $table->string('title_ar', 150);
            $table->string('title_en', 150);
            $table->longText('body_ar')->nullable();
            $table->longText('body_en')->nullable();
            $table->boolean('show_in_footer')->default(true);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'slug']);
            $table->unique(['event_id', 'key']);
        });

        $now = now();
        foreach (DB::table('events')->get() as $event) {
            $currency = (string) (DB::table('ticket_types')->where('event_id', $event->id)->value('currency') ?: 'EGP');
            $data = fn (string $locale) => [
                'name' => $locale === 'ar' ? $event->name_ar : $event->name_en,
                'email' => $event->contact_email,
                'phone' => $event->contact_phone,
                'venue' => $locale === 'ar' ? $event->venue_name_ar : $event->venue_name_en,
                'address' => $locale === 'ar' ? $event->venue_address_ar : $event->venue_address_en,
                'currency' => $currency,
            ];

            foreach (RequiredPage::cases() as $page) {
                DB::table('event_pages')->insert([
                    'event_id' => $event->id, 'key' => $page->value, 'slug' => $page->slug(),
                    'title_ar' => $page->titleAr(), 'title_en' => $page->titleEn(),
                    'body_ar' => PolicyDrafts::render($page, 'ar', $data('ar')),
                    'body_en' => PolicyDrafts::render($page, 'en', $data('en')),
                    'show_in_footer' => true, 'is_published' => true, 'sort_order' => $page->sortOrder(),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_pages');
    }
};
```

- [ ] **Step 7: Model, factory, Event hook, seeder**

`app/Models/EventPage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Enums\RequiredPage;
use App\Support\PolicyDrafts;
use Database\Factories\EventPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A page that belongs to one event: one of the six required policy pages, or a custom one.
 */
class EventPage extends Model
{
    /** @use HasFactory<EventPageFactory> */
    use HasFactory;

    /** A [bracketed placeholder] left in a starter draft. */
    public const PLACEHOLDER_PATTERN = '/\[[^\]\n]{2,80}\]/u';

    protected $fillable = [
        'event_id', 'key', 'slug', 'title_ar', 'title_en', 'body_ar', 'body_en',
        'show_in_footer', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'body_ar' => SanitizedRichText::class,
            'body_en' => SanitizedRichText::class,
            'show_in_footer' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Creates whichever required pages the event doesn't have yet, with starter drafts.
     */
    public static function seedRequiredFor(Event $event): void
    {
        $existing = $event->pages()->whereNotNull('key')->pluck('key')->all();

        foreach (RequiredPage::cases() as $page) {
            if (in_array($page->value, $existing, true)) {
                continue;
            }

            $event->pages()->create([
                'key' => $page->value,
                'slug' => $page->slug(),
                'title_ar' => $page->titleAr(),
                'title_en' => $page->titleEn(),
                'body_ar' => PolicyDrafts::render($page, 'ar', PolicyDrafts::eventData($event, 'ar')),
                'body_en' => PolicyDrafts::render($page, 'en', PolicyDrafts::eventData($event, 'en')),
                'show_in_footer' => true,
                'is_published' => true,
                'sort_order' => $page->sortOrder(),
            ]);
        }
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function requiredPage(): ?RequiredPage
    {
        return $this->key !== null ? RequiredPage::tryFrom($this->key) : null;
    }

    public function isRequired(): bool
    {
        return $this->requiredPage() !== null;
    }

    public function title(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function body(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en;
    }

    /**
     * Still has a [placeholder] from the starter draft in either language.
     */
    public function needsDetails(): bool
    {
        return preg_match(self::PLACEHOLDER_PATTERN, (string) $this->body_ar.' '.$this->body_en) === 1;
    }
}
```

`database/factories/EventPageFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPage>
 */
class EventPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'key' => null,
            'slug' => $this->faker->unique()->slug(2),
            'title_ar' => 'صفحة',
            'title_en' => ucfirst($this->faker->words(2, true)),
            'body_ar' => '<p>محتوى</p>',
            'body_en' => '<p>Content</p>',
            'show_in_footer' => true,
            'is_published' => true,
            'sort_order' => 10,
        ];
    }
}
```

`app/Models/Event.php`: add `pages()`:

```php
    public function pages(): HasMany
    {
        return $this->hasMany(EventPage::class)->orderBy('sort_order')->orderBy('id');
    }
```

and extend the existing `booted()` created hook:

```php
        static::created(function (Event $event) {
            SessionType::seedDefaultsFor($event);
            EventPage::seedRequiredFor($event);
        });
```

`database/seeders/CcsEventSeeder.php`: after the `SessionType::seedDefaultsFor` block add `EventPage::seedRequiredFor($event);` (it skips existing pages) and the import. Note the CCS seeder creates ticket types after the event, so the CCS drafts are seeded **after** the ticket types loop — place the call after the ticket types are created so the currency resolves.

- [ ] **Step 8: Run tests, full suite, commit**

Run: `php artisan test --compact tests/Feature/EventPagesModelTest.php tests/Feature/CcsEventSeederTest.php tests/Feature/DatabaseSeederTest.php` → PASS; `php artisan test --compact` → green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app database resources tests
git commit -m "feat: give every event its required policy pages with starter drafts"
```

---

### Task 2: Admin Pages screens

**Files:**
- Create: `app/Http/Controllers/Admin/EventPageController.php`, `app/Http/Requests/Admin/EventPageRequest.php`, `resources/views/admin/event-pages/index.blade.php`, `resources/views/admin/event-pages/form.blade.php`
- Modify: `app/Enums/Permission.php`, `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php`, `lang/*.json`
- Test: `tests/Feature/Admin/EventPageCrudTest.php` (new); `tests/Unit/AdminPermissionsTest.php` (add a row)

**Interfaces:**
- Consumes: `EventPage`, `RequiredPage` (Task 1).
- Produces: `Permission::Pages` (`'pages'`, label "Pages", group "Event setup", routes `['admin.events.pages.*']`, entry `admin.events.pages.index`); routes `admin.events.pages.{index,create,store,edit,update,destroy,reorder}` (param `page`).

- [ ] **Step 1: Failing tests** — `tests/Feature/Admin/EventPageCrudTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPageCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create();
    }

    public function test_the_list_shows_required_pages_and_flags_drafts(): void
    {
        $this->actingAs($this->admin)->get(route('admin.events.pages.index', ['event' => $this->event, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('Refund & Cancellation Policy')
            ->assertSee('Needs your details')
            ->assertSee('Required');
    }

    public function test_an_admin_adds_edits_and_deletes_a_custom_page(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
            'title_ar' => 'قواعد السلوك', 'title_en' => 'Code of Conduct', 'slug' => 'code-of-conduct',
            'body_en' => '<p>Be kind.</p>', 'body_ar' => '<p>كن لطيفًا.</p>', 'show_in_footer' => '1', 'is_published' => '1',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));

        $page = $this->event->pages()->where('slug', 'code-of-conduct')->sole();
        $this->assertFalse($page->isRequired());
        $this->assertSame(6, $page->sort_order);

        $this->actingAs($this->admin)->put(route('admin.events.pages.update', [$this->event, $page]), [
            'title_ar' => 'قواعد السلوك', 'title_en' => 'Conduct', 'slug' => 'conduct', 'body_en' => '<p>x</p>',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));
        $this->assertSame('conduct', $page->fresh()->slug);
        $this->assertFalse($page->fresh()->is_published);

        $this->actingAs($this->admin)->delete(route('admin.events.pages.destroy', [$this->event, $page]))
            ->assertRedirect(route('admin.events.pages.index', $this->event));
        $this->assertModelMissing($page);
    }

    public function test_slugs_are_url_safe_and_unique_per_event(): void
    {
        foreach (['Code Of Conduct', 'terms', 'a--b'] as $slug) {
            $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
                'title_ar' => 'ص', 'title_en' => 'Page', 'slug' => $slug,
            ])->assertSessionHasErrors('slug');
        }

        EventPage::factory()->create(['slug' => 'faq-extra']);
        $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
            'title_ar' => 'ص', 'title_en' => 'Page', 'slug' => 'faq-extra',
        ])->assertSessionDoesntHaveErrors('slug');
    }

    public function test_a_required_page_cannot_be_deleted(): void
    {
        $terms = $this->event->pages()->where('key', 'terms')->sole();

        $this->actingAs($this->admin)->delete(route('admin.events.pages.destroy', [$this->event, $terms]))->assertForbidden();

        $this->assertModelExists($terms);
    }

    public function test_a_required_page_stays_published_at_its_address(): void
    {
        $refund = $this->event->pages()->where('key', 'refund')->sole();

        $this->actingAs($this->admin)->put(route('admin.events.pages.update', [$this->event, $refund]), [
            'title_ar' => 'الاسترداد', 'title_en' => 'Refunds', 'slug' => 'money-back', 'body_en' => '<p>Ours.</p>',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));

        $refund->refresh();
        $this->assertSame('refund-policy', $refund->slug);
        $this->assertTrue($refund->is_published);
        $this->assertSame('Refunds', $refund->title_en);
        $this->assertFalse($refund->show_in_footer);
    }

    public function test_another_events_page_is_not_found(): void
    {
        $foreign = EventPage::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.events.pages.edit', [$this->event, $foreign]))->assertNotFound();
    }

    public function test_pages_reorder(): void
    {
        $ids = $this->event->pages()->pluck('id')->reverse()->values()->all();

        $this->actingAs($this->admin)->postJson(route('admin.events.pages.reorder', $this->event), ['ids' => $ids])->assertOk();

        $this->assertSame($ids, $this->event->pages()->pluck('id')->all());
    }

    public function test_the_pages_permission_opens_the_section(): void
    {
        $editor = User::factory()->withPermissions(Permission::Pages)->create();

        $this->actingAs($editor)->get(route('admin.events.pages.index', $this->event))->assertOk();
        $this->actingAs(User::factory()->withPermissions(Permission::Speakers)->create())
            ->get(route('admin.events.pages.index', $this->event))->assertForbidden();
    }
}
```

Add to `mappedRoutes()` in `tests/Unit/AdminPermissionsTest.php`: `'pages' => ['admin.events.pages.edit', Permission::Pages],`.

- [ ] **Step 2: Run to verify failure** — `php artisan test --compact tests/Feature/Admin/EventPageCrudTest.php tests/Unit/AdminPermissionsTest.php` → FAIL (route not defined / `Permission::Pages` undefined).

- [ ] **Step 3: Permission** — in `app/Enums/Permission.php` add `case Pages = 'pages';` after `AgendaWorkshops`, and the arms: `label` → `__('Pages')`; `group` → add `self::Pages` to the "Event setup" arm; `routes` → `self::Pages => ['admin.events.pages.*'],`; `entryRoute` → `self::Pages => 'admin.events.pages.index',`.

- [ ] **Step 4: Request**:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var EventPage|null $page */
        $page = $this->route('page');

        return [
            'title_ar' => ['required', 'string', 'max:150'],
            'title_en' => ['required', 'string', 'max:150'],
            // Required pages keep their address; the controller ignores a posted slug for them.
            'slug' => $page?->isRequired() ? ['nullable'] : [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_pages', 'slug')->where('event_id', $event->id)->ignore($page?->id),
            ],
            'body_ar' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'show_in_footer' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['slug.regex' => __('Use lowercase English letters, numbers and single dashes only, e.g. ccs-2026.')];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageAttributes(?EventPage $page): array
    {
        $attributes = [
            'title_ar' => $this->validated('title_ar'),
            'title_en' => $this->validated('title_en'),
            'body_ar' => $this->validated('body_ar'),
            'body_en' => $this->validated('body_en'),
            'show_in_footer' => $this->boolean('show_in_footer'),
        ];

        if (! $page?->isRequired()) {
            $attributes['slug'] = $this->validated('slug');
            $attributes['is_published'] = $this->boolean('is_published');
        }

        return $attributes;
    }
}
```

- [ ] **Step 5: Controller**:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventPageRequest;
use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An event's pages: the six required policy pages (editable, never deleted or unpublished) and
 * any custom ones.
 */
class EventPageController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.event-pages.index', ['event' => $event, 'pages' => $event->pages()->get()]);
    }

    public function create(Event $event): View
    {
        return view('admin.event-pages.form', ['event' => $event, 'page' => new EventPage(['show_in_footer' => true, 'is_published' => true])]);
    }

    public function store(EventPageRequest $request, Event $event): RedirectResponse
    {
        $event->pages()->create($request->pageAttributes(null) + [
            'sort_order' => (int) $event->pages()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, EventPage $page): View
    {
        $this->assertBelongsToEvent($event, $page);

        return view('admin.event-pages.form', ['event' => $event, 'page' => $page]);
    }

    public function update(EventPageRequest $request, Event $event, EventPage $page): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $page);
        $page->update($request->pageAttributes($page));

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, EventPage $page): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $page);
        abort_if($page->isRequired(), 403);

        $page->delete();

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('event_pages', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            EventPage::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function assertBelongsToEvent(Event $event, EventPage $page): void
    {
        if ($page->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
```

- [ ] **Step 6: Routes and sidebar** — `routes/web.php` (import `EventPageController as AdminEventPageController`), inside the admin group:

```php
        Route::post('events/{event}/pages/reorder', [AdminEventPageController::class, 'reorder'])->name('events.pages.reorder');
        Route::resource('events.pages', AdminEventPageController::class)->except('show');
```

Sidebar `$eventSections`, after the Landing Page Content row:

```php
        ['prefix' => 'admin.events.pages', 'permission' => \App\Enums\Permission::Pages, 'route' => 'admin.events.pages.index', 'label' => __('Pages')],
```

- [ ] **Step 7: Views** — `resources/views/admin/event-pages/index.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Pages').' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        <x-admin.button href="{{ route('admin.events.pages.create', $event) }}">{{ __('New Page') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    <p class="mb-4 text-sm text-hub-dark/60">{{ __('Payment gateways check these pages before approving the site. Replace every [placeholder] in the drafts and have them reviewed before going live.') }}</p>

    <x-admin.table>
        <thead>
            <tr><th class="w-10"></th><th>{{ __('Title') }}</th><th>{{ __('Address') }}</th><th>{{ __('Status') }}</th><th>{{ __('Footer') }}</th><th></th></tr>
        </thead>
        <tbody data-sortable data-sortable-url="{{ route('admin.events.pages.reorder', $event) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
            @foreach($pages as $page)
                <tr data-sortable-item="{{ $page->id }}">
                    <td><button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button></td>
                    <td class="font-semibold">{{ $page->title() }}</td>
                    <td dir="ltr" class="text-sm text-hub-dark/60">/pages/{{ $page->slug }}</td>
                    <td class="flex flex-wrap gap-1.5">
                        @if($page->isRequired())<span class="rounded-full bg-hub-lavender px-2 py-0.5 text-xs font-bold text-hub-purple">{{ __('Required') }}</span>@endif
                        @unless($page->is_published)<span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-hub-dark/70">{{ __('Draft') }}</span>@endunless
                        @if($page->needsDetails())<span class="rounded-full bg-[#fdecea] px-2 py-0.5 text-xs font-bold text-[#b42318]">{{ __('Needs your details') }}</span>@endif
                    </td>
                    <td>{{ $page->show_in_footer ? __('Yes') : __('No') }}</td>
                    <td class="text-end whitespace-nowrap">
                        <a href="{{ route('admin.events.pages.edit', [$event, $page]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                        @if($page->is_published)
                            <a href="{{ route('event-pages.show', [$event, $page->slug]) }}" target="_blank" rel="noopener" class="ms-3 text-hub-purple hover:underline">{{ __('View page') }}</a>
                        @endif
                        @unless($page->isRequired())
                            <form method="POST" action="{{ route('admin.events.pages.destroy', [$event, $page]) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endsection
```

(The "View page" link uses the public route added in Task 3; in this task's commit, guard it with `@if(Route::has('event-pages.show'))` — Task 3 removes the guard. Ruling not needed: it's a sequencing aid.)

`resources/views/admin/event-pages/form.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$page->exists ? __('Edit Page') : __('New Page')" />

    <form method="POST" action="{{ $page->exists ? route('admin.events.pages.update', [$event, $page]) : route('admin.events.pages.store', $event) }}">
        @csrf
        @if($page->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $page->title_ar)" :value-en="old('title_en', $page->title_en)" />

        @if($page->isRequired())
            <p class="mb-5 text-sm text-hub-dark/60">{{ __('Address') }}: <span dir="ltr">/pages/{{ $page->slug }}</span> — {{ __('required pages keep their address and stay published.') }}</p>
        @else
            <x-admin.field name="slug" label="{{ __('Address') }}" :value="old('slug', $page->slug)" required />
        @endif

        @if($page->needsDetails())
            <div class="mb-5 rounded border border-[#f5b8b2] bg-[#fdecea] px-4 py-3 text-sm text-[#b42318]">{{ __('This is a starter draft. Replace every [placeholder] and have it reviewed before going live.') }}</div>
        @endif

        <x-admin.bilingual-field type="richtext" name="body" label="{{ __('Content') }}" :value-ar="old('body_ar', $page->body_ar)" :value-en="old('body_en', $page->body_en)" />

        <x-admin.field type="checkbox" name="show_in_footer" label="{{ __('Show in the event footer') }}" :checked="old('show_in_footer', $page->show_in_footer)" />
        @unless($page->isRequired())
            <x-admin.field type="checkbox" name="is_published" label="{{ __('Published') }}" :checked="old('is_published', $page->is_published)" />
        @endunless

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
```

- [ ] **Step 8: Translations** — add: Pages → الصفحات; New Page → صفحة جديدة; Edit Page → تعديل الصفحة; Address → العنوان; Status → الحالة; Footer → التذييل; Required → إلزامية; Draft (exists?) → مسودة; Needs your details → تحتاج بياناتك; View page → عرض الصفحة; Yes → نعم; No → لا; Content → المحتوى; Show in the event footer → إظهارها في تذييل الفعالية; Published → منشورة; `required pages keep their address and stay published.` → `تحتفظ الصفحات الإلزامية بعنوانها وتبقى منشورة.`; `This is a starter draft. Replace every [placeholder] and have it reviewed before going live.` → `هذه مسودة مبدئية. استبدل كل [عنصر بين قوسين] وراجعها قبل النشر.`; `Payment gateways check these pages before approving the site. Replace every [placeholder] in the drafts and have them reviewed before going live.` → `تراجع بوابات الدفع هذه الصفحات قبل اعتماد الموقع. استبدل كل [عنصر بين قوسين] في المسودات وراجعها قبل النشر.` (skip keys that exist; check with grep).

- [ ] **Step 9: Run tests, full suite, commit**

`php artisan test --compact tests/Feature/Admin/EventPageCrudTest.php tests/Unit/AdminPermissionsTest.php tests/Feature/Admin/PermissionAccessTest.php tests/Feature/HubTranslationCoverageTest.php` → PASS; full suite green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app resources routes lang tests
git commit -m "feat: add an admin Pages section for each event's policy pages"
```

---

### Task 3: Public pages, contact page and footer

**Files:**
- Create: `app/Http/Controllers/EventPageController.php`, `resources/views/event-pages/show.blade.php`, `resources/views/landing/partials/contact-form.blade.php`, `resources/views/components/payment-logos.blade.php`
- Modify: `routes/web.php`, `resources/views/landing/partials/contact.blade.php`, `app/Http/Controllers/ContactMessageController.php`, `resources/views/landing/partials/footer.blade.php`, `resources/views/admin/event-pages/index.blade.php` (drop the Task 2 guard), `lang/*.json`
- Test: `tests/Feature/EventPagesPublicTest.php` (new)

**Interfaces:**
- Consumes: `EventPage`, `Event::pages()`.
- Produces: route `event-pages.show` (`GET /events/{event}/pages/{slug}`), view partial `landing.partials.contact-form` (variables: `$event`, optional `$returnTo`).

- [ ] **Step 1: Failing tests** — `tests/Feature/EventPagesPublicTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPagesPublicTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create($attributes + ['status' => EventStatus::Published, 'contact_email' => 'hello@ccs.test', 'contact_phone' => '+20 100 000 0000']);
    }

    public function test_a_page_shows_in_the_readers_language_with_its_last_update(): void
    {
        $event = $this->event();
        $terms = $event->pages()->where('key', 'terms')->sole();
        $terms->update(['body_en' => '<p>English terms.</p>', 'body_ar' => '<p>شروط عربية.</p>']);

        $this->get(route('event-pages.show', [$event, 'terms']).'?lang=en')
            ->assertOk()->assertSee('Terms & Conditions')->assertSee('English terms.')->assertSee('Last updated');
        $this->get(route('event-pages.show', [$event, 'terms']).'?lang=ar')
            ->assertOk()->assertSee('الشروط والأحكام')->assertSee('شروط عربية.');
    }

    public function test_unknown_and_unpublished_pages_are_not_found(): void
    {
        $event = $this->event();
        EventPage::factory()->for($event)->create(['slug' => 'hidden', 'is_published' => false]);

        $this->get(route('event-pages.show', [$event, 'nope']))->assertNotFound();
        $this->get(route('event-pages.show', [$event, 'hidden']))->assertNotFound();
        $this->get(route('event-pages.show', [$this->event(['status' => 'draft']), 'terms']))->assertNotFound();
    }

    public function test_the_contact_page_shows_the_details_and_the_form(): void
    {
        $event = $this->event();

        $this->get(route('event-pages.show', [$event, 'contact']).'?lang=en')
            ->assertOk()
            ->assertSee('mailto:hello@ccs.test', false)
            ->assertSee('tel:+201000000000', false)
            ->assertSee('action="'.route('contact.store', $event).'"', false)
            ->assertSee('name="return_to" value="contact-page"', false);
    }

    public function test_the_contact_page_form_returns_to_the_contact_page(): void
    {
        $event = $this->event();

        $this->post(route('contact.store', $event), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello', 'return_to' => 'contact-page',
        ])->assertRedirect(route('event-pages.show', [$event, 'contact']))->assertSessionHas('contact_success');

        $this->post(route('contact.store', $event), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello again',
        ])->assertRedirect(route('landing.show', $event).'#contact');
    }

    public function test_the_footer_lists_published_footer_pages_in_order(): void
    {
        $event = $this->event();
        EventPage::factory()->for($event)->create(['title_en' => 'Code of Conduct', 'slug' => 'conduct', 'sort_order' => 99]);
        EventPage::factory()->for($event)->create(['title_en' => 'Secret', 'slug' => 'secret', 'is_published' => false]);
        EventPage::factory()->for($event)->create(['title_en' => 'Not in footer', 'slug' => 'quiet', 'show_in_footer' => false]);

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSeeInOrder(['Terms & Conditions', 'Privacy Policy', 'Refund & Cancellation Policy', 'Code of Conduct'])
            ->assertSee(route('event-pages.show', [$event, 'refund-policy']), false)
            ->assertDontSee('Secret')
            ->assertDontSee('Not in footer');
    }

    public function test_policies_show_even_when_the_newsletter_is_off(): void
    {
        $event = $this->event();
        $event->update(['visible_sections' => array_merge($event->visible_sections ?? [], ['newsletter' => false])]);

        $this->get(route('landing.show', $event))
            ->assertSee(route('event-pages.show', [$event, 'terms']), false)
            ->assertDontSee('id="newsletter-email"', false);
    }

    public function test_card_logos_show_only_when_tickets_are_sold(): void
    {
        $free = $this->event();
        $this->get(route('landing.show', $free))->assertDontSee('data-payment-logos', false);

        $paid = $this->event();
        TicketType::factory()->for($paid)->create(['price' => 700, 'is_active' => true]);
        $this->get(route('landing.show', $paid))->assertSee('data-payment-logos', false);
    }
}
```

(Before writing `test_policies_show_even_when_the_newsletter_is_off`, check how `Event::isSectionVisible()` reads its setting — `grep -n "function isSectionVisible" -A8 app/Models/Event.php` — and set the column it reads; adjust the `update([...])` line to that column/shape.)

- [ ] **Step 2: Run to verify failure** — `php artisan test --compact tests/Feature/EventPagesPublicTest.php` → FAIL (route `event-pages.show` not defined).

- [ ] **Step 3: Public controller and route** — `app/Http/Controllers/EventPageController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventPageController extends Controller
{
    public function show(Event $event, string $slug): View
    {
        $page = $event->pages()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('event-pages.show', ['event' => $event, 'page' => $page]);
    }
}
```

`routes/web.php`, inside the `events/{event}` + `EnsureEventIsPublished` group:

```php
    Route::get('/pages/{slug}', [EventPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('event-pages.show');
```

- [ ] **Step 4: Page view** — `resources/views/event-pages/show.blade.php`:

```blade
@extends('layouts.app')

@section('title', $page->title().' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <div class="max-w-3xl">
            <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
                <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
            </a>

            <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-3">{{ $page->title() }}</h1>
            <p class="text-sm text-gray-400 mb-10">{{ __('Last updated: :date', ['date' => $page->updated_at->translatedFormat('j F Y')]) }}</p>

            {{-- Sanitized on save (SanitizedRichText cast) — safe to render unescaped. --}}
            <div class="ccs-richtext text-gray-300 leading-relaxed">{!! $page->body() !!}</div>

            @if($page->key === \App\Enums\RequiredPage::Contact->value)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mt-12">
                    <div class="flex flex-col gap-3 text-gray-300">
                        @if($event->contact_email)
                            <a href="mailto:{{ $event->contact_email }}" class="hover:text-white break-all">{{ $event->contact_email }}</a>
                        @endif
                        @if($event->contact_phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $event->contact_phone) }}" class="hover:text-white" dir="ltr">{{ $event->contact_phone }}</a>
                        @endif
                        @php
                            $venue = app()->getLocale() === 'ar' ? $event->venue_name_ar : $event->venue_name_en;
                            $address = app()->getLocale() === 'ar' ? $event->venue_address_ar : $event->venue_address_en;
                        @endphp
                        @if($venue || $address)
                            <p class="text-gray-400 leading-relaxed">{{ collect([$venue, $address])->filter()->implode(app()->getLocale() === 'ar' ? '، ' : ', ') }}</p>
                        @endif
                    </div>
                    @include('landing.partials.contact-form', ['event' => $event, 'returnTo' => 'contact-page'])
                </div>
            @endif
        </div>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
```

- [ ] **Step 5: Contact form partial and redirect** — move the `<form …>…</form>` from `resources/views/landing/partials/contact.blade.php` into `resources/views/landing/partials/contact-form.blade.php`, adding right after `@csrf`:

```blade
            @isset($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endisset
```

and replace it in `contact.blade.php` with `@include('landing.partials.contact-form', ['event' => $event])`. In `ContactMessageController::store` replace the return with:

```php
        // The Contact page sends people back to itself; the landing page's section to its anchor.
        if ($request->input('return_to') === 'contact-page') {
            return redirect()->route('event-pages.show', [$event, 'contact'])->with('contact_success', true);
        }

        return redirect(route('landing.show', $event).'#contact')->with('contact_success', true);
```

- [ ] **Step 6: Footer** — in `resources/views/landing/partials/footer.blade.php`:
  1. Change the opening `@if($event->isSectionVisible('newsletter'))` + `<footer …>` so the `<footer>` always renders, and only the newsletter block (`<div class="text-center pb-16 mb-16 border-b border-white/10">…</div>`) is wrapped in `@if($event->isSectionVisible('newsletter')) … @endif`; drop the final `@endif` after `</footer>`. Keep `id="newsletter"` on the footer only when the newsletter shows (`@if($event->isSectionVisible('newsletter')) id="newsletter" @endif`).
  2. Change the column grid to `grid-cols-2 md:grid-cols-5` and add, before the "Connect" column:

```blade
        @php $footerPages = $event->pages()->where('is_published', true)->where('show_in_footer', true)->get(); @endphp
        @if($footerPages->isNotEmpty())
            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-1">{{ __('Policies') }}</span>
                @foreach($footerPages as $footerPage)
                    <a href="{{ route('event-pages.show', [$event, $footerPage->slug]) }}" class="text-sm text-gray-300 hover:text-white transition-colors">{{ $footerPage->title() }}</a>
                @endforeach
            </div>
        @endif
```

  3. In the bottom bar, after the copyright span: `@if($event->ticketTypes()->where('is_active', true)->where('price', '>', 0)->exists()) <x-payment-logos /> @endif`.

`resources/views/components/payment-logos.blade.php`:

```blade
{{-- The card schemes the payment gateway accepts, shown where buyers and gateway reviewers
     expect them. Simple marks drawn inline — no image files or icon packages. --}}
<div data-payment-logos class="flex items-center gap-2" aria-label="{{ __('We accept Visa, Mastercard and Meeza') }}" role="img">
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><text x="23" y="19" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" font-style="italic" fill="#1a1f71">VISA</text></svg>
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><circle cx="19" cy="14" r="8" fill="#eb001b"/><circle cx="27" cy="14" r="8" fill="#f79e1b" fill-opacity="0.9"/></svg>
    <svg width="46" height="28" viewBox="0 0 46 28" aria-hidden="true"><rect width="46" height="28" rx="5" fill="#ffffff"/><text x="23" y="18" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" font-weight="700" fill="#0b6e4f">meeza</text></svg>
</div>
```

Remove the `@if(Route::has('event-pages.show'))` guard added in Task 2's index view.

- [ ] **Step 7: Translations** — Last updated: :date → آخر تحديث: :date; Policies → السياسات; We accept Visa, Mastercard and Meeza → نقبل فيزا وماستركارد وميزة.

- [ ] **Step 8: Run tests, full suite, commit**

`php artisan test --compact tests/Feature/EventPagesPublicTest.php tests/Feature/ContactMessageTest.php tests/Feature/LandingPageTest.php tests/Feature/FormPolishTest.php` → PASS; `npm run build`; full suite green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app resources routes lang tests
git commit -m "feat: publish each event's policy pages and link them from the footer"
```

---

### Task 4: Terms agreement on the ticket form and in the approval email

**Files:**
- Create: migration `add_terms_accepted_at_to_tickets_table`
- Modify: `app/Models/Ticket.php`, `app/Http/Requests/TicketRequestStoreRequest.php`, `app/Http/Controllers/TicketRequestController.php`, `resources/views/landing/partials/ticket-request-modal.blade.php`, `resources/views/emails/ticket-requests/approved.blade.php`, `lang/*.json`
- Test: `tests/Feature/TicketTermsAgreementTest.php` (new); add `'accept_terms' => '1'` to existing payloads in `tests/Feature/TicketRequestSubmissionTest.php`, `tests/Feature/DiscountCouponTest.php`, `tests/Feature/PublicFormRateLimitTest.php`

**Interfaces:**
- Consumes: route `event-pages.show` (Task 3).
- Produces: `tickets.terms_accepted_at` (nullable timestamp, cast `datetime`).

- [ ] **Step 1: Failing tests** — `tests/Feature/TicketTermsAgreementTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Mail\TicketRequestApproved;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTermsAgreementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(TicketType $type, array $overrides = []): array
    {
        return $overrides + ['ticket_type_id' => $type->id, 'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567'];
    }

    public function test_a_request_without_agreeing_is_refused(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $type = TicketType::factory()->for($event)->create();

        $this->post(route('ticket-requests.store', $event), $this->payload($type))->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_agreeing_records_when(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $type = TicketType::factory()->for($event)->create();
        $this->travelTo(now()->setTime(12, 0));

        $this->post(route('ticket-requests.store', $event), $this->payload($type, ['accept_terms' => '1']))->assertSessionHasNoErrors();

        $this->assertSame(now()->toDateTimeString(), Ticket::sole()->terms_accepted_at->toDateTimeString());
    }

    public function test_the_form_links_both_policies(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create();

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSee('name="accept_terms"', false)
            ->assertSee(route('event-pages.show', [$event, 'terms']), false)
            ->assertSee(route('event-pages.show', [$event, 'refund-policy']), false);
    }

    public function test_the_approval_email_links_both_policies(): void
    {
        $ticket = Ticket::factory()->create();

        $html = (new TicketRequestApproved($ticket, 'https://pay.example/1'))->render();

        $this->assertStringContainsString(route('event-pages.show', [$ticket->event, 'terms']), $html);
        $this->assertStringContainsString(route('event-pages.show', [$ticket->event, 'refund-policy']), $html);
    }
}
```

(Check `TicketRequestApproved`'s constructor signature — `grep -n "__construct" app/Mail/TicketRequestApproved.php` — and match it.)

- [ ] **Step 2: Run to verify failure** — `php artisan test --compact tests/Feature/TicketTermsAgreementTest.php` → FAIL.

- [ ] **Step 3: Column** — `php artisan make:migration add_terms_accepted_at_to_tickets_table --no-interaction`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // When the requester agreed to the event's Terms and Refund Policy (null for tickets
            // requested before the agreement existed, and for invitation tickets).
            $table->timestamp('terms_accepted_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
```

`Ticket`: add `'terms_accepted_at'` to `$fillable` and `'terms_accepted_at' => 'datetime'` to `casts()`.

- [ ] **Step 4: Validation and save** — `TicketRequestStoreRequest::rules()` add `'accept_terms' => ['accepted'],`; `attributes()` add `$attributes['accept_terms'] = __('agreement');` and `messages()` add `'accept_terms.accepted' => __('Please agree to the Terms & Conditions and the Refund & Cancellation Policy.')` (merge with the existing messages array). `TicketRequestController`: add `'terms_accepted_at' => now(),` to the `$event->tickets()->create([...])` array.

- [ ] **Step 5: Form** — in `ticket-request-modal.blade.php`, right before the submit button:

```blade
                    <div>
                        <label class="flex items-start gap-3 text-sm text-white/80 leading-relaxed">
                            <input type="checkbox" name="accept_terms" value="1" class="mt-1 w-4 h-4 accent-ccs-coral" @checked(old('accept_terms')) aria-required="true">
                            <span>{!! __('I have read and agree to the :terms and the :refund.', [
                                'terms' => '<a href="'.e(route('event-pages.show', [$event, 'terms'])).'" target="_blank" rel="noopener" class="text-ccs-coral underline">'.e(__('Terms & Conditions')).'</a>',
                                'refund' => '<a href="'.e(route('event-pages.show', [$event, 'refund-policy'])).'" target="_blank" rel="noopener" class="text-ccs-coral underline">'.e(__('Refund & Cancellation Policy')).'</a>',
                            ]) !!}</span>
                        </label>
                        <p id="error-accept_terms" class="ccs-form-error {{ $errors->has('accept_terms') ? '' : 'hidden' }}">{{ $errors->first('accept_terms') }}</p>
                    </div>
```

(The `{!! !!}` is safe: the translated sentence comes from our JSON files and the inserted links are built with `e()`.)

- [ ] **Step 6: Approval email** — in `approved.blade.php`, after the reference table:

```blade
    <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#6b6b6b;">
        {!! __('By paying you agree to the :terms and the :refund.', [
            'terms' => '<a href="'.e(route('event-pages.show', [$ticket->event, 'terms'])).'" style="color:#3c3489;">'.e(__('Terms & Conditions')).'</a>',
            'refund' => '<a href="'.e(route('event-pages.show', [$ticket->event, 'refund-policy'])).'" style="color:#3c3489;">'.e(__('Refund & Cancellation Policy')).'</a>',
        ]) !!}
    </p>
```

- [ ] **Step 7: Existing tests** — in `TicketRequestSubmissionTest.php`, `DiscountCouponTest.php` and `PublicFormRateLimitTest.php`, add `'accept_terms' => '1'` to every payload array posted to `ticket-requests.store` that is meant to succeed (scripted: insert `'accept_terms' => '1', ` immediately after each `'ticket_type_id' => …,` occurrence in those files). Tests that check validation errors for missing fields keep working because the error they assert is still present.

- [ ] **Step 8: Translations** — `I have read and agree to the :terms and the :refund.` → `قرأت وأوافق على :terms و:refund.`; `By paying you agree to the :terms and the :refund.` → `بالدفع فأنت توافق على :terms و:refund.`; `Terms & Conditions` → الشروط والأحكام; `Refund & Cancellation Policy` → سياسة الاسترداد والإلغاء; `agreement` → الموافقة; `Please agree to the Terms & Conditions and the Refund & Cancellation Policy.` → `يرجى الموافقة على الشروط والأحكام وسياسة الاسترداد والإلغاء.`

- [ ] **Step 9: Run tests, full suite, commit**

`php artisan test --compact tests/Feature/TicketTermsAgreementTest.php tests/Feature/TicketRequestSubmissionTest.php tests/Feature/DiscountCouponTest.php tests/Feature/PublicFormRateLimitTest.php tests/Feature/HubTranslationCoverageTest.php` → PASS; full suite green.

```bash
vendor/bin/pint --dirty --format agent
git add -A app database resources lang tests
git commit -m "feat: ask buyers to agree to the Terms and Refund Policy and link them in the payment email"
```

---

### Task 5: Dev database and a browser check

- [ ] **Step 1:** `php artisan migrate --force` → both migrations run. Check: `php artisan tinker --execute 'echo App\Models\EventPage::count()." pages, ".App\Models\Event::count()." events";'` → 6 × events.
- [ ] **Step 2:** `npm run build`.
- [ ] **Step 3:** Browser (Playwright, scratchpad): as admin open `/admin/events/ccs-2026/pages` (screenshot; "Needs your details" badges), edit the Refund page title and save, check Delete is absent for required pages; open `/events/ccs-2026/pages/refund-policy` in Arabic and English (screenshots), the Contact page (details + form; submit the form and confirm it returns to the Contact page with the thanks message), the event footer (Policies column + card logos), and the ticket form (checkbox with two links; submitting without ticking shows the error). Report page errors. Undo the test edit and delete the test contact message afterwards.
- [ ] **Step 4:** `php artisan test --compact` → green; report the count.

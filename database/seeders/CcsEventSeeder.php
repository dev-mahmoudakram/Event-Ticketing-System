<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AgendaItemType;
use App\Enums\EventStatus;
use App\Enums\LandingPageSection;
use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\LandingPageContent;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Testimonial;
use App\Models\TicketType;
use App\Models\TicketTypeFeature;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

class CcsEventSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::create([
            'slug' => 'ccs-2026',
            'name_ar' => 'قمة صناع المحتوى',
            'name_en' => 'Content Creators Summit',
            'tagline_ar' => 'أثر يتوالى',
            'tagline_en' => 'Impact that continues',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-16',
            'venue_name_ar' => 'مركز القاهرة الدولي للمؤتمرات',
            'venue_name_en' => 'Cairo International Convention Centre',
            'venue_address_ar' => 'مدينة نصر، القاهرة، جمهورية مصر العربية',
            'venue_address_en' => 'Nasr City, Cairo, Egypt',
            'map_embed_url' => 'https://maps.google.com/?q=Cairo+International+Convention+Centre',
            'status' => EventStatus::Published,
            'visible_sections' => array_merge(
                array_fill_keys(Event::TOGGLEABLE_SECTIONS, true),
                ['stats' => false],
            ),
        ]);

        $speakerKareem = Speaker::create([
            'event_id' => $event->id,
            'name_ar' => 'كريم السيد',
            'name_en' => 'Kareem Al-Sayed',
            'title_ar' => 'صانع محتوى',
            'title_en' => 'Content Creator',
            'bio_ar' => 'صانع محتوى رقمي بخبرة تتجاوز عشر سنوات.',
            'bio_en' => 'Digital content creator with over a decade of experience.',
            'photo_path' => 'https://picsum.photos/seed/ccs-speaker-kareem/400/400',
            'sort_order' => 1,
        ]);
        Speaker::create([
            'event_id' => $event->id,
            'name_ar' => 'نور إبراهيم',
            'name_en' => 'Nour Ibrahim',
            'title_ar' => 'مؤسسة استوديو محتوى',
            'title_en' => 'Founder, Content Studio',
            'bio_ar' => 'مؤسسة استوديو إنتاج محتوى مقره القاهرة، تركز أعمالها على صناعة المحتوى القصير.',
            'bio_en' => 'Founder of a Cairo-based content production studio focused on short-form storytelling.',
            'photo_path' => 'https://picsum.photos/seed/ccs-speaker-nour/400/400',
            'sort_order' => 2,
        ]);
        Speaker::create([
            'event_id' => $event->id,
            'name_ar' => 'عمر خالد',
            'name_en' => 'Omar Khaled',
            'title_ar' => 'خبير تسويق رقمي',
            'title_en' => 'Digital Marketing Strategist',
            'bio_ar' => 'خبير تسويق رقمي يساعد صناع المحتوى على بناء جمهور حقيقي ومستدام.',
            'bio_en' => 'Digital marketing strategist helping creators build a real, sustainable audience.',
            'photo_path' => 'https://picsum.photos/seed/ccs-speaker-omar/400/400',
            'sort_order' => 3,
        ]);
        Speaker::create([
            'event_id' => $event->id,
            'name_ar' => 'سلمى حسن',
            'name_en' => 'Salma Hassan',
            'title_ar' => 'منتجة بودكاست',
            'title_en' => 'Podcast Producer',
            'bio_ar' => 'منتجة بودكاست تعمل مع صناع المحتوى على تحويل أفكارهم إلى حلقات مسموعة.',
            'bio_en' => 'Podcast producer who turns creator ideas into finished, listenable episodes.',
            'photo_path' => 'https://picsum.photos/seed/ccs-speaker-extra/400/400',
            'sort_order' => 4,
        ]);

        $sponsors = [
            ['name_ar' => 'نايل تك', 'name_en' => 'Nile Tech', 'tier' => 'platinum'],
            ['name_ar' => 'استوديوهات القاهرة', 'name_en' => 'Cairo Studios', 'tier' => 'platinum'],
            ['name_ar' => 'الراعي الذهبي', 'name_en' => 'Golden Sponsor Co.', 'tier' => 'gold'],
            ['name_ar' => 'دلتا ميديا', 'name_en' => 'Delta Media', 'tier' => 'gold'],
            ['name_ar' => 'أسكندرية كرييتف', 'name_en' => 'Alexandria Creative', 'tier' => 'gold'],
            ['name_ar' => 'مجتمع صناع المحتوى', 'name_en' => 'Creators Community', 'tier' => 'community'],
            ['name_ar' => 'هَب المحتوى', 'name_en' => 'Content Hub', 'tier' => 'community'],
            ['name_ar' => 'استوديو الجيزة', 'name_en' => 'Giza Studio', 'tier' => 'community'],
            ['name_ar' => 'شبكة المبدعين', 'name_en' => 'Creators Network', 'tier' => 'community'],
        ];
        foreach ($sponsors as $i => $sponsor) {
            Sponsor::create([
                ...$sponsor,
                'event_id' => $event->id,
                'logo_path' => 'https://placehold.co/240x100?text='.urlencode($sponsor['name_en']),
                'sort_order' => $i,
            ]);
        }

        $ticketSlots = [
            [
                'name_en' => 'General', 'name_ar' => 'عام', 'slots' => 0, 'price' => 300,
                'features' => [
                    ['ar' => 'دخول كامل للحدث', 'en' => 'Full event access'],
                    ['ar' => 'الكلمات الرئيسية والجلسات', 'en' => 'Keynotes & sessions'],
                    ['ar' => 'دخول منطقة المعرض', 'en' => 'Expo floor access'],
                ],
            ],
            [
                'name_en' => 'VIP', 'name_ar' => 'كبار الشخصيات', 'slots' => 1, 'price' => 700,
                'features' => [
                    ['ar' => 'كل مزايا باقة عام', 'en' => 'Everything in General'],
                    ['ar' => 'ورشة عمل واحدة من اختيارك', 'en' => '1 workshop of your choice'],
                    ['ar' => 'جلوس بالأولوية', 'en' => 'Priority seating'],
                ],
            ],
            [
                'name_en' => 'Premium', 'name_ar' => 'مميز', 'slots' => 2, 'price' => 1200,
                'features' => [
                    ['ar' => 'كل مزايا باقة كبار الشخصيات', 'en' => 'Everything in VIP'],
                    ['ar' => 'ورشتا عمل من اختيارك', 'en' => '2 workshops of your choice'],
                    ['ar' => 'دخول الصالة الخاصة', 'en' => 'Lounge access'],
                ],
            ],
            [
                'name_en' => 'Platinum', 'name_ar' => 'بلاتيني', 'slots' => null, 'price' => 2500,
                'features' => [
                    ['ar' => 'كل مزايا باقة مميز', 'en' => 'Everything in Premium'],
                    ['ar' => '3 ورش عمل من اختيارك', 'en' => '3 workshops of your choice'],
                    ['ar' => 'عشاء خاص وجلوس بالصف الأول', 'en' => 'Private dinner & front-row seating'],
                ],
            ],
        ];
        foreach ($ticketSlots as $i => $tier) {
            $ticketType = TicketType::create([
                'event_id' => $event->id,
                'name_ar' => $tier['name_ar'],
                'name_en' => $tier['name_en'],
                'description_ar' => 'وصف الباقة',
                'description_en' => $tier['name_en'].' ticket tier',
                'price' => $tier['price'],
                'currency' => 'EGP',
                'workshop_slot_count' => $tier['slots'],
                'sort_order' => $i,
                'is_active' => true,
            ]);
            foreach ($tier['features'] as $j => $feature) {
                TicketTypeFeature::create([
                    'ticket_type_id' => $ticketType->id,
                    'text_ar' => $feature['ar'],
                    'text_en' => $feature['en'],
                    'sort_order' => $j,
                ]);
            }
        }

        $workshop = Workshop::create([
            'event_id' => $event->id,
            'speaker_id' => $speakerKareem->id,
            'slug' => 'ai-content-workshop',
            'name_ar' => 'ورشة صناعة المحتوى بالذكاء الاصطناعي',
            'name_en' => 'AI-Powered Content Creation Workshop',
            'description_ar' => 'تعلم كيفية استخدام أدوات الذكاء الاصطناعي في صناعة المحتوى.',
            'description_en' => 'Learn how to use AI tools in content creation.',
            'capacity' => 40,
            'sort_order' => 1,
        ]);
        Workshop::create([
            'event_id' => $event->id,
            'slug' => 'short-form-editing-workshop',
            'name_ar' => 'ورشة مونتاج المحتوى القصير',
            'name_en' => 'Short-Form Editing Workshop',
            'description_ar' => 'جلسة عملية في مونتاج المقاطع القصيرة وإيقاعها.',
            'description_en' => 'A hands-on session on cutting and pacing short-form video.',
            'capacity' => 30,
            'sort_order' => 2,
        ]);

        AgendaItem::create([
            'event_id' => $event->id,
            'speaker_id' => $speakerKareem->id,
            'workshop_id' => $workshop->id,
            'day_date' => '2026-08-15',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'title_ar' => $workshop->name_ar,
            'title_en' => $workshop->name_en,
            'type' => AgendaItemType::WorkshopSession,
            'sort_order' => 1,
        ]);

        $faqs = [
            [
                'question_ar' => 'كيف أحصل على تذكرة؟',
                'question_en' => 'How do I get a ticket?',
                'answer_ar' => 'قدّم طلبك من صفحة التذاكر وسيتم مراجعته من قبل الإدارة.',
                'answer_en' => 'Submit a request from the Tickets section; it will be reviewed by the admin team.',
            ],
            [
                'question_ar' => 'متى أدفع؟',
                'question_en' => 'When do I pay?',
                'answer_ar' => 'بعد الموافقة على طلبك، ستصلك رسالة تحتوي على رابط الدفع.',
                'answer_en' => 'After your request is approved, you will receive an email with a payment link.',
            ],
            [
                'question_ar' => 'كيف أختار ورش العمل؟',
                'question_en' => 'How do I choose my workshops?',
                'answer_ar' => 'بعد إصدار التذكرة، استخدم معرّف التذكرة ومفتاح الحجز لاختيار ورش العمل.',
                'answer_en' => 'Once your ticket is issued, use your Ticket ID and Booking Key to pick your workshops.',
            ],
        ];
        foreach ($faqs as $i => $faq) {
            Faq::create([...$faq, 'event_id' => $event->id, 'sort_order' => $i]);
        }

        $content = [
            [LandingPageSection::Hero, 'headline', 'قمة صناع المحتوى ٢٠٢٦', 'Content Creators Summit 2026'],
            [LandingPageSection::About, 'body', 'قمة صناع المحتوى هي ملتقى محترفي المحتوى الرقمي في مصر والمنطقة العربية.', 'Content Creators Summit is where digital content professionals from Egypt and the region meet.'],
            [LandingPageSection::Location, 'intro', 'يقام الحدث في مركز القاهرة الدولي للمؤتمرات.', 'The event takes place at the Cairo International Convention Centre.'],
            [LandingPageSection::AwardsTeaser, 'blurb', 'صوّت لصانع المحتوى المفضل لديك قريبًا.', 'Vote for your favorite content creator — coming soon.'],
            [LandingPageSection::Stats, 'attendees_count', '٢٬٥٠٠+', '2,500+'],
            [LandingPageSection::Stats, 'countries_count', '١٢', '12'],
        ];
        foreach ($content as [$section, $key, $ar, $en]) {
            LandingPageContent::create([
                'event_id' => $event->id,
                'section' => $section,
                'field_key' => $key,
                'value_ar' => $ar,
                'value_en' => $en,
            ]);
        }

        $testimonials = [
            [
                'quote_ar' => 'قمة صناع المحتوى غيّرت الطريقة التي أفكر بها في عملي كصانع محتوى.',
                'quote_en' => 'CCS changed how I think about my work as a content creator.',
                'name_ar' => 'سارة محمود',
                'name_en' => 'Sara Mahmoud',
                'title_ar' => 'صانعة محتوى مستقلة',
                'title_en' => 'Independent Creator',
            ],
            [
                'quote_ar' => 'ورش العمل كانت عملية جدًا ومليئة بالخبرات الحقيقية.',
                'quote_en' => 'The workshops were hands-on and full of real experience.',
                'name_ar' => 'أحمد فتحي',
                'name_en' => 'Ahmed Fathy',
                'title_ar' => 'مدير تسويق محتوى',
                'title_en' => 'Content Marketing Lead',
            ],
            [
                'quote_ar' => 'خرجت من القمة بعلاقات وشراكات حقيقية لعملي.',
                'quote_en' => 'I left the summit with real partnerships for my work.',
                'name_ar' => 'مريم حسن',
                'name_en' => 'Mariam Hassan',
                'title_ar' => 'مؤسسة استوديو إبداعي',
                'title_en' => 'Founder, Creative Studio',
            ],
        ];
        foreach ($testimonials as $i => $testimonial) {
            Testimonial::create([...$testimonial, 'event_id' => $event->id, 'sort_order' => $i]);
        }

        foreach (range(1, 6) as $i) {
            GalleryPhoto::create([
                'event_id' => $event->id,
                'image_path' => "https://picsum.photos/seed/ccs-gallery-{$i}/800/800",
                'sort_order' => $i,
            ]);
        }
    }
}

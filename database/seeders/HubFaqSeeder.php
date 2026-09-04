<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteFaq;
use Illuminate\Database\Seeder;

class HubFaqSeeder extends Seeder
{
    /**
     * Starter questions for the Creators Hub landing page.
     *
     * The FAQ section stays hidden until questions exist, so these give it something to show on
     * a fresh install. They describe how the platform actually works and are meant to be edited
     * or deleted under Creators Hub FAQs — the seeder only fills an empty table, so nothing an
     * admin writes is ever overwritten.
     */
    public function run(): void
    {
        if (SiteFaq::query()->exists()) {
            return;
        }

        $faqs = [
            [
                'question_en' => 'How do I get a ticket?',
                'question_ar' => 'كيف أحصل على تذكرة؟',
                'answer_en' => 'Request one from the event page — no account needed. Our team reviews every request and emails you the next step once it is approved. Your ticket arrives with a QR code once everything is confirmed.',
                'answer_ar' => 'اطلبها من صفحة الفعالية دون الحاجة إلى حساب. يراجع فريقنا كل طلب ويرسل لك الخطوة التالية بالبريد الإلكتروني بعد الموافقة، وتصلك التذكرة برمز QR بعد اكتمال الإجراءات.',
            ],
            [
                'question_en' => 'Do I need an account?',
                'question_ar' => 'هل أحتاج إلى حساب؟',
                'answer_en' => 'No. Requesting a ticket, paying for it, and checking in all work from the links we email you.',
                'answer_ar' => 'لا. طلب التذكرة ودفع قيمتها وتسجيل الدخول للفعالية تتم جميعها عبر الروابط التي نرسلها إليك بالبريد الإلكتروني.',
            ],
            [
                'question_en' => 'What do I bring on the day?',
                'question_ar' => 'ماذا أحضر معي يوم الفعالية؟',
                'answer_en' => 'The QR code on your ticket. The registration team scans it at the door to check you in.',
                'answer_ar' => 'رمز QR الموجود على تذكرتك. يقوم فريق التسجيل بمسحه عند المدخل لتسجيل حضورك.',
            ],
            [
                'question_en' => 'Is the site available in Arabic?',
                'question_ar' => 'هل الموقع متاح بالعربية؟',
                'answer_en' => 'Yes. Every page is available in Arabic and English — switch language from the navigation bar.',
                'answer_ar' => 'نعم. كل الصفحات متاحة بالعربية والإنجليزية، ويمكنك تبديل اللغة من شريط التنقل.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            SiteFaq::create($faq + ['sort_order' => $index]);
        }
    }
}

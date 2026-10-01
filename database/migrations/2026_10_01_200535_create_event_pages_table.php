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

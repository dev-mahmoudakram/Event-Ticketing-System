<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-event lists the agenda picks from: session types (with the five the old fixed list had)
 * and locations. New events get the default types from SessionType::seedDefaultsFor(); existing
 * events get them here, written out so this migration doesn't depend on the model.
 */
return new class extends Migration
{
    /** @var list<array{string, string, bool}> */
    private const DEFAULT_TYPES = [
        ['كلمة رئيسية', 'Keynote', false],
        ['جلسة', 'Session', false],
        ['ورشة عمل', 'Workshop', false],
        ['استراحة', 'Break', true],
        ['حلقة نقاشية', 'Panel', false],
    ];

    public function up(): void
    {
        Schema::create('session_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            // Sessions of a break type show as a slim line on the agenda.
            $table->boolean('is_break')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // The links from sessions and workshops. Nullable here; the restructure migration fills
        // session_type_id for every session and then makes it required.
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreignId('session_type_id')->nullable()->after('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->after('session_type_id')->constrained()->nullOnDelete();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        $now = now();
        foreach (DB::table('events')->pluck('id') as $eventId) {
            foreach (self::DEFAULT_TYPES as $position => [$nameAr, $nameEn, $isBreak]) {
                DB::table('session_types')->insert([
                    'event_id' => $eventId, 'name_ar' => $nameAr, 'name_en' => $nameEn,
                    'is_break' => $isBreak, 'sort_order' => $position, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('session_type_id');
        });

        Schema::dropIfExists('locations');
        Schema::dropIfExists('session_types');
    }
};

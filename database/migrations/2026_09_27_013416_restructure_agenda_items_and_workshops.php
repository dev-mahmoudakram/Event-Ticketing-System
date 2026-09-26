<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions get a description and several ordered speakers, and their type moves to the event's
 * session types; workshops get their own schedule and speakers and stop needing an agenda item to
 * appear on the schedule. The session_type_id / location_id columns were added (nullable) by the
 * session-types migration; this one fills them. Old values are read as plain strings so the old
 * enum can be deleted.
 */
return new class extends Migration
{
    /** @var array<string, string> old agenda_items.type => default type's English name */
    private const TYPE_NAMES = [
        'keynote' => 'Keynote', 'session' => 'Session', 'workshop' => 'Workshop', 'break' => 'Break', 'panel' => 'Panel',
    ];

    public function up(): void
    {
        // Every old link is read before the first schema change: on SQLite, altering a table
        // rebuilds it, and dropping the old copy fires the foreign keys pointing at it (setting
        // agenda_items.workshop_id to null, deleting pivot rows). MySQL doesn't, but reading first
        // is right for both.
        $sessionSpeakers = DB::table('agenda_items')->whereNotNull('speaker_id')->whereNull('workshop_id')->pluck('speaker_id', 'id');
        $workshopSpeakers = DB::table('workshops')->whereNotNull('speaker_id')->pluck('speaker_id', 'id');
        $linked = DB::table('agenda_items')->whereNotNull('workshop_id')->orderBy('day_date')->orderBy('start_time')->get();

        // A workshop with no speaker of its own keeps the speaker its agenda slot named.
        foreach ($linked->groupBy('workshop_id') as $workshopId => $items) {
            $slotSpeaker = $items->firstWhere('speaker_id', '!=', null)?->speaker_id;
            if (! $workshopSpeakers->has($workshopId) && $slotSpeaker !== null) {
                $workshopSpeakers->put($workshopId, $slotSpeaker);
            }
        }

        Schema::create('agenda_item_speaker', function (Blueprint $table) {
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['agenda_item_id', 'speaker_id']);
        });

        Schema::create('speaker_workshop', function (Blueprint $table) {
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['workshop_id', 'speaker_id']);
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->longText('description_ar')->nullable()->after('title_en');
            $table->longText('description_en')->nullable()->after('description_ar');
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->date('day_date')->nullable()->after('name_en');
            $table->time('start_time')->nullable()->after('day_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->longText('description_ar')->nullable()->change();
            $table->longText('description_en')->nullable()->change();
        });

        foreach (DB::table('agenda_items')->get() as $item) {
            $typeName = self::TYPE_NAMES[$item->type] ?? 'Session';
            $typeId = DB::table('session_types')->where('event_id', $item->event_id)->where('name_en', $typeName)->value('id')
                ?? DB::table('session_types')->where('event_id', $item->event_id)->orderBy('sort_order')->value('id');

            DB::table('agenda_items')->where('id', $item->id)->update(['session_type_id' => $typeId]);
        }

        // A workshop takes over the schedule of the (first) agenda item that pointed at it; that
        // item would then duplicate the workshop on the merged schedule, so it goes.
        foreach ($linked->groupBy('workshop_id') as $workshopId => $items) {
            $first = $items->first();
            DB::table('workshops')->where('id', $workshopId)->update([
                'day_date' => $first->day_date, 'start_time' => $first->start_time, 'end_time' => $first->end_time,
            ]);
        }
        DB::table('agenda_items')->whereIn('id', $linked->pluck('id'))->delete();

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
            $table->dropConstrainedForeignId('workshop_id');
            $table->dropColumn('type');
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->unsignedBigInteger('session_type_id')->nullable(false)->change();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
        });

        foreach ($sessionSpeakers as $itemId => $speakerId) {
            DB::table('agenda_item_speaker')->insert(['agenda_item_id' => $itemId, 'speaker_id' => $speakerId, 'sort_order' => 0]);
        }

        foreach ($workshopSpeakers as $workshopId => $speakerId) {
            DB::table('speaker_workshop')->insert(['workshop_id' => $workshopId, 'speaker_id' => $speakerId, 'sort_order' => 0]);
        }
    }

    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->unsignedBigInteger('session_type_id')->nullable()->change();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->foreignId('speaker_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreignId('speaker_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->foreignId('workshop_id')->nullable()->after('speaker_id')->constrained()->nullOnDelete();
            $table->string('type')->default('session')->after('title_en');
        });

        foreach (DB::table('agenda_items')->get(['id', 'session_type_id']) as $item) {
            $name = DB::table('session_types')->where('id', $item->session_type_id)->value('name_en');
            $type = array_search($name, self::TYPE_NAMES, true) ?: 'session';
            $speakerId = DB::table('agenda_item_speaker')->where('agenda_item_id', $item->id)->orderBy('sort_order')->value('speaker_id');
            DB::table('agenda_items')->where('id', $item->id)->update(['type' => $type, 'speaker_id' => $speakerId]);
        }

        foreach (DB::table('workshops')->pluck('id') as $workshopId) {
            $speakerId = DB::table('speaker_workshop')->where('workshop_id', $workshopId)->orderBy('sort_order')->value('speaker_id');
            DB::table('workshops')->where('id', $workshopId)->update(['speaker_id' => $speakerId]);
        }

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropColumn(['description_ar', 'description_en']);
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn(['day_date', 'start_time', 'end_time']);
            $table->text('description_ar')->nullable()->change();
            $table->text('description_en')->nullable()->change();
        });

        Schema::dropIfExists('speaker_workshop');
        Schema::dropIfExists('agenda_item_speaker');
    }
};

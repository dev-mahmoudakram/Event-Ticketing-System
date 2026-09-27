<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleRestructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_and_workshops_keep_several_speakers_in_order(): void
    {
        $event = Event::factory()->create();
        [$a, $b, $c] = Speaker::factory()->for($event)->count(3)->create()->all();
        $session = AgendaItem::factory()->for($event)->create();
        $workshop = Workshop::factory()->for($event)->create();

        $session->syncSpeakersInOrder([$c->id, $a->id, $b->id]);
        $workshop->syncSpeakersInOrder([$b->id, $a->id]);

        $this->assertSame([$c->id, $a->id, $b->id], $session->fresh()->speakers->pluck('id')->all());
        $this->assertSame([$b->id, $a->id], $workshop->fresh()->speakers->pluck('id')->all());

        $session->syncSpeakersInOrder([$a->id]);
        $this->assertSame([$a->id], $session->fresh()->speakers->pluck('id')->all());
    }

    public function test_deleting_a_speaker_removes_them_from_sessions_and_workshops(): void
    {
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();
        $session = AgendaItem::factory()->for($event)->create();
        $workshop = Workshop::factory()->for($event)->create();
        $session->syncSpeakersInOrder([$speaker->id]);
        $workshop->syncSpeakersInOrder([$speaker->id]);

        $speaker->delete();

        $this->assertCount(0, $session->fresh()->speakers);
        $this->assertCount(0, $workshop->fresh()->speakers);
    }

    public function test_a_session_has_a_type_a_location_and_a_rich_description(): void
    {
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create(['name_en' => 'Main Stage']);
        $session = AgendaItem::factory()->for($event)->ofType('Panel')->create([
            'location_id' => $location->id,
            'description_en' => '<p>Hello</p><script>alert(1)</script>',
        ]);

        $this->assertSame('Panel', $session->sessionType->name_en);
        $this->assertSame('Main Stage', $session->location->name_en);
        $this->assertStringNotContainsString('<script>', $session->fresh()->description_en);
    }

    public function test_a_location_used_only_by_a_workshop_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create();
        Workshop::factory()->for($event)->create(['location_id' => $location->id]);

        $this->actingAs($admin)->delete(route('admin.events.locations.destroy', [$event, $location]).'?lang=en')
            ->assertSessionHas('error', 'In use by 1 session or workshop — move it first.');

        $this->assertModelExists($location);
    }

    public function test_a_session_type_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $session = AgendaItem::factory()->for($event)->ofType('Keynote')->create();

        $this->actingAs($admin)->delete(route('admin.events.session-types.destroy', [$event, $session->sessionType]))
            ->assertSessionHas('error');

        $this->assertModelExists($session->sessionType);
    }

    /**
     * Rolls just the restructure migration back, recreates the old shape of data, and migrates
     * again. Revisit if a later migration comes to depend on the new columns.
     */
    public function test_existing_agenda_and_workshops_move_to_the_new_structure(): void
    {
        $migration = collect(glob(database_path('migrations/*_restructure_agenda_items_and_workshops.php')))->sole();
        $this->artisan('migrate:rollback', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();
        $workshopId = DB::table('workshops')->insertGetId([
            'event_id' => $event->id, 'speaker_id' => $speaker->id, 'slug' => 'w-1', 'name_ar' => 'و', 'name_en' => 'W',
            'capacity' => 10, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $row = fn (array $extra) => $extra + [
            'event_id' => $event->id, 'day_date' => '2026-08-15', 'title_ar' => 'ع', 'title_en' => 'T',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('agenda_items')->insert($row(['type' => 'panel', 'speaker_id' => $speaker->id, 'start_time' => '09:00', 'end_time' => '10:00', 'title_en' => 'Kept panel']));
        DB::table('agenda_items')->insert($row(['type' => 'mystery', 'start_time' => '10:00', 'end_time' => '11:00', 'title_en' => 'Odd type']));
        DB::table('agenda_items')->insert($row(['type' => 'workshop', 'workshop_id' => $workshopId, 'start_time' => '14:00', 'end_time' => '15:30', 'title_en' => 'Workshop slot']));
        // A workshop with no speaker of its own, whose agenda slot named one.
        $slotSpeaker = Speaker::factory()->for($event)->create();
        $bareWorkshopId = DB::table('workshops')->insertGetId([
            'event_id' => $event->id, 'speaker_id' => null, 'slug' => 'w-2', 'name_ar' => 'و', 'name_en' => 'W2',
            'capacity' => 10, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('agenda_items')->insert($row(['type' => 'workshop', 'workshop_id' => $bareWorkshopId, 'speaker_id' => $slotSpeaker->id, 'start_time' => '16:00', 'end_time' => '17:00', 'title_en' => 'Bare slot']));

        $this->artisan('migrate', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $panel = AgendaItem::where('title_en', 'Kept panel')->sole();
        $this->assertSame('Panel', $panel->sessionType->name_en);
        $this->assertSame([$speaker->id], $panel->speakers->pluck('id')->all());
        $this->assertSame('Session', AgendaItem::where('title_en', 'Odd type')->sole()->sessionType->name_en);
        $this->assertDatabaseMissing('agenda_items', ['title_en' => 'Workshop slot']);

        $workshop = Workshop::findOrFail($workshopId);
        $this->assertSame('2026-08-15', $workshop->day_date->toDateString());
        $this->assertSame('14:00', $workshop->start_time->format('H:i'));
        $this->assertSame('15:30', $workshop->end_time->format('H:i'));
        $this->assertSame([$speaker->id], $workshop->speakers->pluck('id')->all());
        $this->assertSame([$slotSpeaker->id], Workshop::findOrFail($bareWorkshopId)->speakers->pluck('id')->all());
    }
}

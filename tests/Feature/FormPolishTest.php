<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\TicketRequestField;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FormPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dropdowns_are_marked_for_the_styled_select(): void
    {
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.events.edit', $event))
            ->assertOk()
            ->assertSee('<select name="status" id="status" data-nice-select', false);
    }

    public function test_the_admin_layout_carries_the_confirm_dialog_button_labels(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.events.index', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('data-confirm-yes="Confirm"', false)
            ->assertSee('data-confirm-no="Cancel"', false)
            ->assertSee('data-confirm-delete="Delete"', false);
    }

    public function test_the_ticket_request_form_uses_the_shared_form_styles(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create();
        $instagram = TicketRequestField::factory()->for($event)->create(['type' => 'instagram', 'label_en' => 'Instagram', 'is_required' => true]);

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertOk()
            ->assertSee('class="ccs-form-submit"', false)
            ->assertSee('id="field_'.$instagram->id.'"', false)
            ->assertSee('name="field_'.$instagram->id.'_followers"', false)
            ->assertSee('id="error-field_'.$instagram->id.'_followers"', false)
            ->assertSee('ccs-form-required', false);
    }

    /**
     * Teal was taken out of the event palette; this keeps it from drifting back in through a
     * copied class name or a hard-coded value.
     */
    public function test_teal_is_no_longer_part_of_the_palette(): void
    {
        $offenders = collect(File::allFiles(resource_path()))
            ->filter(fn ($file) => preg_match('/ccs-teal|#7ccbcf|#2a7675/i', $file->getContents()) === 1)
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders);
    }

    public function test_ticket_cards_share_rows_so_they_match_in_height(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->count(2)->create(['is_active' => true]);

        $this->get(route('landing.show', $event))
            ->assertOk()
            ->assertSee('md:grid-rows-subgrid md:row-span-6', false)
            ->assertDontSee('md:grid-cols-4 gap-6 items-start', false);
    }
}

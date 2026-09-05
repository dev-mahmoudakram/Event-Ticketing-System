<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FieldComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Blade::render() bypasses the `web` middleware group, so the
        // ShareErrorsFromSession middleware never shares $errors with the
        // view. Share an empty error bag so the component's @error
        // directive has something to inspect, matching real request behavior.
        $this->withViewErrors([]);
    }

    public function test_text_field_renders_label_and_input(): void
    {
        $html = Blade::render('<x-admin.field name="name_en" label="Name (English)" value="Jane" />');

        $this->assertStringContainsString('for="name_en"', $html);
        $this->assertStringContainsString('Name (English)', $html);
        $this->assertStringContainsString('name="name_en"', $html);
        $this->assertStringContainsString('value="Jane"', $html);
    }

    public function test_rtl_field_gets_dir_attribute(): void
    {
        $html = Blade::render('<x-admin.field name="name_ar" dir="rtl" />');

        $this->assertStringContainsString('dir="rtl"', $html);
    }

    public function test_select_field_renders_slot_options(): void
    {
        $html = Blade::render('<x-admin.field type="select" name="status"><option value="draft">Draft</option></x-admin.field>');

        $this->assertStringContainsString('<select name="status"', $html);
        $this->assertStringContainsString('<option value="draft">Draft</option>', $html);
    }

    public function test_checkbox_field_renders_checked_state(): void
    {
        $html = Blade::render('<x-admin.field type="checkbox" name="is_active" label="Active" :checked="true" />');

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function test_field_shows_validation_error_when_present(): void
    {
        $this->withViewErrors(['name_en' => 'The name en field is required.']);

        $html = Blade::render('<x-admin.field name="name_en" label="Name" />');

        $this->assertStringContainsString('text-[#b42318]', $html);
        $this->assertStringContainsString('The name en field is required.', $html);
    }
}

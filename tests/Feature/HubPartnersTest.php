<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HubPartner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HubPartnersTest extends TestCase
{
    use RefreshDatabase;

    private function partner(array $attributes = []): HubPartner
    {
        return HubPartner::create(array_merge([
            'name_en' => 'Acme',
            'name_ar' => 'أكمي',
            'logo_path' => 'hub-partners/acme.png',
            'sort_order' => 0,
        ], $attributes));
    }

    public function test_the_partner_strip_is_absent_until_partners_exist(): void
    {
        $this->get(route('home').'?lang=en')->assertDontSee('aria-label="Partners"', false);
    }

    public function test_a_partner_logo_is_rendered(): void
    {
        $this->partner();

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('/storage/hub-partners/acme.png', false);
        $response->assertSee('alt="Acme"', false);
    }

    public function test_a_partner_without_a_logo_falls_back_to_its_name(): void
    {
        $this->partner(['logo_path' => null, 'name_en' => 'Nameless Co']);

        $this->get(route('home').'?lang=en')->assertSee('Nameless Co');
    }

    public function test_a_partner_with_a_site_links_out_safely(): void
    {
        $this->partner(['website_url' => 'https://example.com']);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('href="https://example.com"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_arrows_appear_only_when_the_strip_can_scroll(): void
    {
        foreach (range(1, 3) as $i) {
            $this->partner(['name_en' => 'Partner '.$i, 'sort_order' => $i]);
        }

        // Three tiles fit, so there is nothing to scroll to yet.
        $this->get(route('home').'?lang=en')->assertDontSee('aria-label="Next partners"', false);

        $this->partner(['name_en' => 'Partner 4', 'sort_order' => 4]);

        $this->get(route('home').'?lang=en')->assertSee('aria-label="Next partners"', false);
    }

    public function test_partners_render_in_sort_order(): void
    {
        $this->partner(['name_en' => 'Second', 'logo_path' => null, 'sort_order' => 2]);
        $this->partner(['name_en' => 'First', 'logo_path' => null, 'sort_order' => 1]);

        $html = $this->get(route('home').'?lang=en')->getContent();

        $this->assertLessThan(strpos($html, 'Second'), strpos($html, 'First'));
    }

    public function test_an_admin_can_add_a_partner(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.hub-partners.store'), [
            'name_en' => 'Nile Materials',
            'name_ar' => 'مواد النيل',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.hub-partners.index'));
        $partner = HubPartner::where('name_en', 'Nile Materials')->firstOrFail();
        Storage::disk('public')->assertExists($partner->logo_path);
    }

    public function test_a_new_partner_requires_a_logo_and_a_name(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.hub-partners.store'), [])
            ->assertSessionHasErrors(['logo', 'name_en', 'name_ar']);
    }

    public function test_deleting_a_partner_removes_its_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        Storage::disk('public')->put('hub-partners/gone.png', 'x');
        $partner = $this->partner(['logo_path' => 'hub-partners/gone.png']);

        $this->actingAs($admin)->delete(route('admin.hub-partners.destroy', $partner));

        $this->assertDatabaseMissing('hub_partners', ['id' => $partner->id]);
        Storage::disk('public')->assertMissing('hub-partners/gone.png');
    }

    public function test_guests_cannot_manage_partners(): void
    {
        $this->get(route('admin.hub-partners.index'))->assertRedirect(route('admin.login'));
    }
}

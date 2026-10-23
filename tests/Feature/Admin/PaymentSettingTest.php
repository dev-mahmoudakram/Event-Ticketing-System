<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\PaymentMode;
use App\Enums\Permission;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentSettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validInput(): array
    {
        return [
            'mode' => 'live',
            'merchant_id' => 'MID-1234-567',
            'api_key' => 'live-payment-api-key',
            'secret_key' => 'live-secret-key-value',
        ];
    }

    public function test_an_admin_saves_the_kashier_account(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.payment-settings.update'), $this->validInput())
            ->assertRedirect(route('admin.payment-settings.edit'))
            ->assertSessionHas('success');

        $settings = PaymentSetting::current();
        $this->assertSame(PaymentMode::Live, $settings->mode);
        $this->assertSame('MID-1234-567', $settings->merchant_id);
        $this->assertSame('live-payment-api-key', $settings->api_key);
        $this->assertSame('live-secret-key-value', $settings->secret_key);
        $this->assertTrue($settings->isComplete());
        $this->assertSame(1, PaymentSetting::count());
    }

    public function test_the_keys_are_encrypted_at_rest(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.payment-settings.update'), $this->validInput());

        $row = DB::table('payment_settings')->first();
        $this->assertStringNotContainsString('live-payment-api-key', (string) $row->api_key);
        $this->assertStringNotContainsString('live-secret-key-value', (string) $row->secret_key);
    }

    public function test_the_saved_keys_are_never_shown_back(): void
    {
        $admin = User::factory()->create();
        PaymentSetting::create($this->validInput());

        $this->actingAs($admin)->get(route('admin.payment-settings.edit'))
            ->assertOk()
            ->assertSee('MID-1234-567')
            ->assertDontSee('live-payment-api-key')
            ->assertDontSee('live-secret-key-value');
    }

    public function test_blank_key_fields_keep_the_saved_keys(): void
    {
        $admin = User::factory()->create();
        PaymentSetting::create($this->validInput());

        $this->actingAs($admin)->put(route('admin.payment-settings.update'), [
            'mode' => 'test',
            'merchant_id' => 'MID-9999-111',
            'api_key' => '',
            'secret_key' => '',
        ])->assertSessionHasNoErrors();

        $settings = PaymentSetting::current();
        $this->assertSame(PaymentMode::Test, $settings->mode);
        $this->assertSame('MID-9999-111', $settings->merchant_id);
        $this->assertSame('live-payment-api-key', $settings->api_key);
        $this->assertSame('live-secret-key-value', $settings->secret_key);
    }

    public function test_the_keys_are_required_the_first_time(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.payment-settings.update'), [
            'mode' => 'test',
            'merchant_id' => 'MID-1234-567',
        ])->assertSessionHasErrors(['api_key', 'secret_key']);

        $this->assertSame(0, PaymentSetting::count());
    }

    public function test_the_mode_and_merchant_id_are_validated(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.payment-settings.update'), [
            ...$this->validInput(),
            'mode' => 'sandbox',
            'merchant_id' => 'not-a-merchant',
        ])->assertSessionHasErrors(['mode', 'merchant_id']);
    }

    public function test_staff_with_every_permission_still_cannot_open_payment_settings(): void
    {
        $staff = User::factory()->withPermissions(...Permission::cases())->create();

        $this->actingAs($staff)->get(route('admin.payment-settings.edit'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.payment-settings.update'), $this->validInput())->assertForbidden();

        $this->assertSame(0, PaymentSetting::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.payment-settings.edit'))->assertRedirect(route('admin.login'));
    }
}

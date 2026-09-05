<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

class LoginPageDesignTest extends TestCase
{
    public function test_the_login_page_wears_the_creators_hub_identity(): void
    {
        $response = $this->get(route('admin.login').'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Creators Hub');
        $response->assertSee('creators-hub/mark.png', false);
        $response->assertSee('adm-body', false);
        $response->assertDontSee('ccs-flag-accent', false);
    }

    public function test_the_login_page_asks_not_to_be_indexed(): void
    {
        $this->get(route('admin.login'))
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_a_failed_attempt_says_so(): void
    {
        $response = $this->post(route('admin.login'), ['email' => 'nobody@example.com', 'password' => 'wrong']);

        $this->followRedirects($response)->assertSee('text-[#b42318]', false);
    }
}

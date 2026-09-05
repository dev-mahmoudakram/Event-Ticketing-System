<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubMobileMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_language_switch_stays_outside_the_menu(): void
    {
        $html = $this->get(route('home').'?lang=en')->getContent();

        $bar = substr($html, 0, strpos($html, 'x-show="open"'));
        $menu = substr($html, strpos($html, 'x-show="open"'));

        $this->assertStringContainsString('lang=en', $bar, 'The switch belongs in the bar, at every width.');
        $this->assertStringNotContainsString('?lang=ar', substr($menu, 0, strpos($menu, '</header>')), 'The menu should not repeat the switch.');
    }

    public function test_the_buttons_move_into_the_menu_on_small_screens(): void
    {
        $html = $this->get(route('home').'?lang=en')->getContent();
        $menu = substr($html, strpos($html, 'x-show="open"'));
        $menu = substr($menu, 0, strpos($menu, '</header>'));

        $this->assertStringContainsString('Contact', $menu);
        $this->assertStringContainsString('Explore Events', $menu);
        // In the bar they only appear once there is no menu button to hold them. The wrapper
        // carries the hiding, since .hub-pill sets its own display and would outrank `hidden`.
        $bar = substr($html, 0, strpos($html, 'x-show="open"'));
        $this->assertStringContainsString('<div class="hidden lg:flex items-center gap-3">', $bar);
    }

    public function test_the_bar_squares_off_while_the_menu_is_open(): void
    {
        $this->get(route('home').'?lang=en')
            ->assertSee("'rounded-b-3xl': ! open", false);
    }
}

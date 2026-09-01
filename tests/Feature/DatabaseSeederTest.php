<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_seeder_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => config('admin.seed_email')]);
        $this->assertNotNull(Event::where('slug', 'ccs-2026')->first());
    }

    public function test_seeding_twice_does_not_duplicate_the_admin(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', config('admin.seed_email'))->count());
    }

    public function test_the_seeded_admin_password_is_configurable(): void
    {
        config(['admin.seed_email' => 'ops@example.com', 'admin.seed_password' => 'a-real-secret']);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(auth()->validate([
            'email' => 'ops@example.com',
            'password' => 'a-real-secret',
        ]));
    }

    public function test_the_database_seeder_can_be_run_twice(): void
    {
        // Deployments re-run db:seed; the second pass must not collide on the event's unique
        // slug or duplicate its demo content.
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Event::where('slug', 'ccs-2026')->count());
        $this->assertSame(1, User::where('email', config('admin.seed_email'))->count());
    }

    public function test_reseeding_leaves_existing_event_content_untouched(): void
    {
        $this->seed(DatabaseSeeder::class);
        $event = Event::where('slug', 'ccs-2026')->firstOrFail();
        $speakerCount = $event->speakers()->count();
        $event->update(['name_en' => 'Renamed By An Admin']);

        $this->seed(DatabaseSeeder::class);

        // An admin's edits survive, and the child content is not duplicated on top of them.
        $this->assertSame('Renamed By An Admin', $event->fresh()->name_en);
        $this->assertSame($speakerCount, $event->speakers()->count());
    }

    /**
     * Seeders run on deployments installed with `composer install --no-dev`, where
     * fakerphp/faker is absent and Laravel therefore never defines fake(). Model factories
     * call fake() in their definitions, so a factory reached from a seeder is a production
     * crash rather than a style issue.
     */
    public function test_no_seeder_depends_on_model_factories(): void
    {
        foreach (glob(database_path('seeders/*.php')) as $seeder) {
            $source = file_get_contents($seeder);
            $source = preg_replace('#/\*.*?\*/#s', '', $source);
            $source = preg_replace('#//.*#', '', $source);

            $this->assertStringNotContainsString('factory()', $source, basename($seeder).' calls a model factory, which needs faker.');
            $this->assertStringNotContainsString('fake()', $source, basename($seeder).' calls fake(), which is unavailable without dev dependencies.');
        }
    }
}

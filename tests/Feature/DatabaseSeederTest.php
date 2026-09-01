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

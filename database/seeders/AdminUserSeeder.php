<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds the single admin account.
     *
     * Deliberately builds the user directly rather than through a factory: factories call
     * fake(), which Laravel only defines when fakerphp/faker is installed, and faker is a dev
     * dependency that a production `composer install --no-dev` leaves out.
     */
    public function run(): void
    {
        $email = config('admin.seed_email');
        $password = config('admin.seed_password');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.seed_name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        if ($password === 'password123') {
            $this->command?->warn(
                'Seeded the admin with the default development password. Set ADMIN_SEED_PASSWORD '
                .'(and ADMIN_SEED_EMAIL) before seeding anywhere public, then re-run this seeder.'
            );
        }
    }
}

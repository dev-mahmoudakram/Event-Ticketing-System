<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    private const DEVELOPMENT_PASSWORD = 'password123';

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

        // Refused outright in production: this seeder overwrites the admin's password, so
        // re-running it there without ADMIN_SEED_PASSWORD would reset the live login to a
        // password published in this repository.
        if ($password === self::DEVELOPMENT_PASSWORD && app()->isProduction()) {
            throw new RuntimeException('Set ADMIN_SEED_PASSWORD before seeding the admin in production.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.seed_name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        if ($password === self::DEVELOPMENT_PASSWORD) {
            $this->command?->warn(
                'Seeded the admin with the default development password. Set ADMIN_SEED_PASSWORD '
                .'(and ADMIN_SEED_EMAIL) before seeding anywhere public, then re-run this seeder.'
            );
        }
    }
}

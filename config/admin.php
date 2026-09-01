<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Admin Account
    |--------------------------------------------------------------------------
    |
    | Credentials AdminUserSeeder uses for the initial admin login. The defaults
    | exist so a fresh local checkout works out of the box; override them via the
    | environment on any deployment that is reachable from outside your machine.
    |
    */

    'seed_name' => env('ADMIN_SEED_NAME', 'CCS Admin'),
    'seed_email' => env('ADMIN_SEED_EMAIL', 'admin@ccs.test'),
    'seed_password' => env('ADMIN_SEED_PASSWORD', 'password123'),

];

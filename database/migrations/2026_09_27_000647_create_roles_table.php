<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff roles become records the admin manages, built from the permissions in
 * App\Enums\Permission. Permissions are written as plain strings here so this migration keeps
 * working however the enum changes later.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const STARTING_ROLES = [
        'Speakers' => ['speakers', 'speaker_requests'],
        'Sponsors' => ['sponsors', 'sponsor_requests'],
        'Sales' => ['ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
        'Project Manager' => ['dashboard', 'speakers', 'speaker_requests', 'sponsors', 'sponsor_requests', 'ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
        'Registration Desk' => ['registration_desk'],
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->json('permissions');
            // The built-in Admin role: locked, and always granted everything.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert(['name' => 'Admin', 'permissions' => '[]', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now]);

        foreach (self::STARTING_ROLES as $name => $permissions) {
            DB::table('roles')->insert(['name' => $name, 'permissions' => json_encode($permissions), 'is_system' => false, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->restrictOnDelete();
        });

        $adminRoleId = DB::table('roles')->where('is_system', true)->value('id');
        $deskRoleId = DB::table('roles')->where('name', 'Registration Desk')->value('id');

        DB::table('users')->where('role', 'admin')->update(['role_id' => $adminRoleId]);
        // Check-in staff, and anyone with a value this app never used, get the least access.
        DB::table('users')->whereNull('role_id')->update(['role_id' => $deskRoleId]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email');
        });

        $adminRoleIds = DB::table('roles')->where('is_system', true)->pluck('id');
        DB::table('users')->whereIn('role_id', $adminRoleIds)->update(['role' => 'admin']);
        DB::table('users')->whereNotIn('role_id', $adminRoleIds)->update(['role' => 'check_in']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};

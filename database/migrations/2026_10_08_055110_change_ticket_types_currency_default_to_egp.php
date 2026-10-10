<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments go through Kashier, which charges in Egyptian pounds, so EGP is the platform's
     * currency. The SAR rows were placeholder data and are moved over with the default.
     */
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('currency', 3)->default('EGP')->change();
        });

        DB::table('ticket_types')->where('currency', 'SAR')->update(['currency' => 'EGP']);
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('currency', 3)->default('SAR')->change();
        });
    }
};

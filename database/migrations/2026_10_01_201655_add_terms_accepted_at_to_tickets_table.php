<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // When the requester agreed to the event's Terms and Refund Policy (null for tickets
            // requested before the agreement existed, and for invitation tickets).
            $table->timestamp('terms_accepted_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};

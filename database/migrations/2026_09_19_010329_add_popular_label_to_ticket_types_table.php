<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            // Null means the card falls back to the default "Most Popular" wording — an
            // admin only needs to fill this in to change it.
            $table->string('popular_label_ar')->nullable()->after('is_popular');
            $table->string('popular_label_en')->nullable()->after('popular_label_ar');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn(['popular_label_ar', 'popular_label_en']);
        });
    }
};

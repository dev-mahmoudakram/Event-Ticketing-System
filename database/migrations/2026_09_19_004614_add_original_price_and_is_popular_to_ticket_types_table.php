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
            // The pre-discount price, shown struck through beside the real price. Null means
            // no sale is running.
            $table->unsignedInteger('original_price')->nullable()->after('price');
            // The admin's manual pick for which single tier gets the standout card treatment
            // on the public page, replacing the old "highest price wins" default.
            $table->boolean('is_popular')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'is_popular']);
        });
    }
};

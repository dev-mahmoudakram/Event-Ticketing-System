<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The time of day check-in opens on the event's own date(s).
     *
     * A ticket could previously be scanned the moment it was issued, days or weeks before
     * the event — a QR code emailed at approval time works at the door just as well the
     * instant it arrives. Storing a clock time (not a datetime) means the same time applies
     * on whichever day of a multi-day event the door opens.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->time('check_in_starts_at')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('check_in_starts_at');
        });
    }
};

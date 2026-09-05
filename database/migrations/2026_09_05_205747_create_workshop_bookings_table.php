<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which ticket holds a place in which workshop.
     *
     * A ticket may book several workshops, up to its ticket type's slot allowance, but never
     * the same one twice — that is what the unique key is for. Capacity is counted from the
     * rows here rather than kept as a running total, so it cannot drift.
     */
    public function up(): void
    {
        Schema::create('workshop_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['ticket_id', 'workshop_id']);
            $table->index('workshop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_bookings');
    }
};

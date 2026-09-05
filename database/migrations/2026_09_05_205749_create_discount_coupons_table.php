<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Codes an attendee types on the ticket request form.
     *
     * A coupon belongs to one event, so two events can run the same code without colliding —
     * hence the unique key on the pair rather than on the code alone. The amount is stored the
     * way the ticket price is (integer minor units) for percentage-free arithmetic.
     */
    public function up(): void
    {
        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('type', 10)->default('percentage'); // percentage | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('usage_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_coupons');
    }
};

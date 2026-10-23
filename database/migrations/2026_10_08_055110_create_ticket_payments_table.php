<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per payment link sent: the Kashier session behind it and what became of it.
     *
     * A ticket gets a new row each time its link is resent, so the history of every link stays.
     * merchant_order_id is the reference Kashier knows the payment by — it must never repeat
     * for the merchant, hence unique. The amount is what was asked for, in the ticket's units.
     */
    public function up(): void
    {
        Schema::create('ticket_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('merchant_order_id', 100)->unique();
            $table->string('session_id', 100)->nullable()->index();
            $table->text('session_url')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending')->index();
            $table->string('transaction_id', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_payments');
    }
};

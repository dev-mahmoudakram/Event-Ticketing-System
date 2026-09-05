<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One vote, cast by a member of the public against a confirmed email address.
     *
     * A vote counts only once its email has been confirmed, so an unconfirmed row is a pending
     * vote rather than a cast one. The unique key is per event, per category, per email: one
     * person votes once in each category, and changing their mind replaces the row.
     *
     * The email is what identifies a voter, so it is stored in full — the confirmation link
     * needs a real address to send to, and a hash could not be re-mailed.
     */
    public function up(): void
    {
        Schema::create('award_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->string('category_en');
            $table->string('email');
            $table->string('confirmation_token', 64)->nullable()->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'category_en', 'email']);
            $table->index(['award_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_votes');
    }
};

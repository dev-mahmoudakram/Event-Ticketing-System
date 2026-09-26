<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32);
            $table->foreignId('influencer_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('influencer_category_other')->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->unsignedInteger('instagram_followers')->nullable();
            $table->string('facebook_url', 2048)->nullable();
            $table->unsignedInteger('facebook_followers')->nullable();
            $table->string('tiktok_url', 2048)->nullable();
            $table->unsignedInteger('tiktok_followers')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('ticket_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_requests');
    }
};

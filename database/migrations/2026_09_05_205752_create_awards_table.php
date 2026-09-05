<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The nominees, grouped by category.
     *
     * A category is a plain bilingual string on the nominee rather than its own table: an event
     * has a handful of them, they are edited alongside the nominees, and a separate table would
     * buy nothing but a join. The voting window lives on the event's award settings below.
     */
    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('category_ar');
            $table->string('category_en');
            $table->string('nominee_name_ar');
            $table->string('nominee_name_en');
            $table->text('nominee_bio_ar')->nullable();
            $table->text('nominee_bio_en')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'category_en']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('voting_opens_at')->nullable()->after('social_links');
            $table->timestamp('voting_closes_at')->nullable()->after('voting_opens_at');
            $table->boolean('show_award_results')->default(false)->after('voting_closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['voting_opens_at', 'voting_closes_at', 'show_award_results']);
        });

        Schema::dropIfExists('awards');
    }
};

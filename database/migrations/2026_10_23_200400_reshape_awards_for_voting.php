<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reshape awards into award categories with their own nominees, votes and email codes.
     *
     * The old tables stored one nominee per row and nothing wrote to them, so they are replaced
     * rather than converted. If they hold data the migration refuses to run.
     *
     * Safe to re-run after a half-failed attempt: the guard still runs first, then every table
     * this creates is dropped (children before parents) and the events columns are checked.
     */
    public function up(): void
    {
        foreach (['awards', 'award_votes'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('awards/award_votes already hold data; reshape them by hand before migrating.');
            }
        }

        Schema::dropIfExists('award_vote_codes');
        Schema::dropIfExists('award_votes');
        Schema::dropIfExists('award_nominees');
        Schema::dropIfExists('awards');

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });

        Schema::create('award_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['award_id', 'sort_order']);
        });

        Schema::create('award_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_nominee_id')->constrained('award_nominees')->cascadeOnDelete();
            $table->string('email');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['award_id', 'email']);
            $table->index(['award_id', 'ip_address', 'created_at']);
            $table->index(['award_nominee_id', 'removed_at']);
        });

        Schema::create('award_vote_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_nominee_id')->constrained('award_nominees')->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['award_id', 'email']);
        });

        if (! Schema::hasColumn('events', 'voting_mode')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('voting_mode')->default('schedule')->after('voting_closes_at');
            });
        }

        if (Schema::hasColumn('events', 'show_award_results')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('show_award_results');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('show_award_results')->default(false)->after('voting_closes_at');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('voting_mode');
        });

        Schema::dropIfExists('award_vote_codes');
        Schema::dropIfExists('award_votes');
        Schema::dropIfExists('award_nominees');
        Schema::dropIfExists('awards');

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
};

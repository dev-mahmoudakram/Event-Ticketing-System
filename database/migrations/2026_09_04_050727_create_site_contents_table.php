<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable copy for the Creators Hub landing page.
     *
     * Deliberately separate from landing_page_content, which is scoped to an event: Creators
     * Hub is the platform itself, not an event. Reusing that table with a nullable event_id
     * would also defeat its (event_id, section, field_key) unique index, since MySQL permits
     * repeated rows wherever part of a unique key is NULL.
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('field_key');
            $table->text('value_ar')->nullable();
            $table->text('value_en')->nullable();
            $table->timestamps();

            $table->unique(['section', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};

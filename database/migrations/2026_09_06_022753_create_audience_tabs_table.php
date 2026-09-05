<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The sides of the "Who it is for" section.
     *
     * This began as two fixed sides written into the site-content registry, which meant a
     * third audience could not be added without editing PHP. A tab is a row now, so the
     * section describes however many audiences the event actually has.
     */
    public function up(): void
    {
        Schema::create('audience_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->text('lede_ar')->nullable();
            $table->text('lede_en')->nullable();
            $table->string('cta_ar')->nullable();
            $table->string('cta_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_tabs');
    }
};

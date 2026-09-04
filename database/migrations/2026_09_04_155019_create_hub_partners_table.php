<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partner logos for the Creators Hub landing page.
     *
     * Separate from the per-event sponsors table: those belong to a specific event and are
     * managed under that event, whereas these belong to the platform itself.
     */
    public function up(): void
    {
        Schema::create('hub_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_partners');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cards inside each audience tab.
     *
     * Previously four fixed slots per side, named one through four in the content registry.
     * A card is a row now, so a tab carries as many as it needs and they can be reordered
     * without renaming anything.
     */
    public function up(): void
    {
        Schema::create('audience_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_tab_id')->constrained()->cascadeOnDelete();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_cards');
    }
};

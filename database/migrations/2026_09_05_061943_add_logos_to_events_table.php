<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An event's own logo, in the two places a logo appears. They are separate columns because
     * a footer usually needs a lighter version of the same mark.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('cover_image_path');
            $table->string('footer_logo_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'footer_logo_path']);
        });
    }
};

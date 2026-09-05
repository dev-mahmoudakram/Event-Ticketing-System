<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An event is its own brand: its own tab icon, its own link preview, its own contact
     * details and social accounts, all separate from the platform's.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('favicon_path')->nullable()->after('cover_image_path');
            $table->string('apple_touch_icon_path')->nullable()->after('favicon_path');
            $table->string('share_image_path')->nullable()->after('apple_touch_icon_path');
            $table->string('contact_email')->nullable()->after('map_embed_url');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->json('social_links')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'favicon_path', 'apple_touch_icon_path', 'share_image_path',
                'contact_email', 'contact_phone', 'social_links',
            ]);
        });
    }
};

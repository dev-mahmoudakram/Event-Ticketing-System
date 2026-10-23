<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The platform's Kashier account, edited from the admin dashboard. One row for every event.
     *
     * The two keys are stored encrypted with the app key (text columns, because the ciphertext
     * is far longer than the key), so a database dump alone cannot take payments or forge a
     * webhook. Rotating APP_KEY means entering them again.
     */
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 10)->default('test');
            $table->string('merchant_id', 100)->nullable();
            $table->text('api_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};

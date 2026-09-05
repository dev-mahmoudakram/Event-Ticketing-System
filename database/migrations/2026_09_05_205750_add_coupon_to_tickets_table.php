<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a ticket actually costs, and why.
     *
     * The price is copied onto the ticket at request time rather than read back from the ticket
     * type, because a type's price can change afterwards and a sold ticket must remember what
     * was agreed. The coupon reference survives the coupon being deleted.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('discount_coupon_id')->nullable()->after('ticket_type_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('price')->nullable()->after('discount_coupon_id');
            $table->unsignedInteger('discount_amount')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_coupon_id');
            $table->dropColumn(['price', 'discount_amount']);
        });
    }
};

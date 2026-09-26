<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Invitation tickets issued before this fix were recorded at their ticket type's full price,
 * so reports counted each invited guest as a sale. They cost the guest nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')
            ->where('payment_method', 'invitation')
            ->update(['price' => 0, 'discount_amount' => 0]);
    }

    public function down(): void
    {
        // The original list prices aren't worth restoring: they were never charged.
    }
};

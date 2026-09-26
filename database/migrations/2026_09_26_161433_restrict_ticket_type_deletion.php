<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stops the database deleting sold tickets and sent invitations when their ticket type is
 * deleted. The admin controller already refuses; this is the backstop for any other path.
 */
return new class extends Migration
{
    private const TABLES = ['tickets', 'invitations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['ticket_type_id']);
                $blueprint->foreign('ticket_type_id')->references('id')->on('ticket_types')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['ticket_type_id']);
                $blueprint->foreign('ticket_type_id')->references('id')->on('ticket_types')->cascadeOnDelete();
            });
        }
    }
};

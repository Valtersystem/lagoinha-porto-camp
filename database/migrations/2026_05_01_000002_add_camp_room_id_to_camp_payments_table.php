<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('camp_payments', function (Blueprint $table) {
            $table
                ->foreignId('camp_room_id')
                ->nullable()
                ->after('camp_lot_id')
                ->constrained('camp_rooms')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('camp_room_id');
        });
    }
};

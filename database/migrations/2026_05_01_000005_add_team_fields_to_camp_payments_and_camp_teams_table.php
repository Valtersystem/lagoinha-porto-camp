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
                ->foreignId('camp_team_id')
                ->nullable()
                ->after('camp_room_id')
                ->constrained('camp_teams')
                ->nullOnDelete();
        });

        Schema::table('camp_teams', function (Blueprint $table) {
            $table
                ->foreignId('leader_camp_payment_id')
                ->nullable()
                ->after('camp_id')
                ->constrained('camp_payments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leader_camp_payment_id');
        });

        Schema::table('camp_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('camp_team_id');
        });
    }
};

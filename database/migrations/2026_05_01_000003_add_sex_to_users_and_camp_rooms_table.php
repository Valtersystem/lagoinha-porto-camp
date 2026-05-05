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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sex')->nullable()->after('email');
        });

        Schema::table('camp_rooms', function (Blueprint $table) {
            $table->string('sex')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_rooms', function (Blueprint $table) {
            $table->dropColumn('sex');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sex');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_verification_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('camp_verification_point_id')
                ->constrained('camp_verification_points')
                ->cascadeOnDelete();
            $table->foreignId('camp_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 32);
            $table->timestamp('verified_at');
            $table->string('user_name');
            $table->string('user_email');
            $table->string('user_phone')->nullable();
            $table->string('user_role_name')->nullable();
            $table->string('team_name')->nullable();
            $table->string('room_name')->nullable();
            $table->string('sex_label')->nullable();
            $table->timestamps();

            $table->unique(
                ['camp_verification_point_id', 'camp_payment_id'],
                'camp_verification_entries_point_payment_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_verification_entries');
    }
};

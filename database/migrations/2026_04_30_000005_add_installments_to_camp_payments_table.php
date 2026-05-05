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
            $table->unsignedSmallInteger('installments_count')->default(1)->after('amount_cents');
            $table->unsignedSmallInteger('paid_installments')->default(0)->after('installments_count');
            $table->unsignedInteger('amount_paid_cents')->default(0)->after('paid_installments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_payments', function (Blueprint $table) {
            $table->dropColumn([
                'installments_count',
                'paid_installments',
                'amount_paid_cents',
            ]);
        });
    }
};

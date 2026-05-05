<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pin', 4)->nullable();
            $table->string('verification_code', 32)->nullable();
        });

        $usedPins = DB::table('users')
            ->whereNotNull('pin')
            ->pluck('pin')
            ->all();
        $usedCodes = DB::table('users')
            ->whereNotNull('verification_code')
            ->pluck('verification_code')
            ->all();

        $users = DB::table('users')
            ->whereNull('pin')
            ->orWhereNull('verification_code')
            ->orderBy('id')
            ->get(['id', 'pin', 'verification_code']);

        foreach ($users as $user) {
            $pin = $user->pin ?: $this->generateUniquePin($usedPins);
            $code = $user->verification_code ?: $this->generateUniqueVerificationCode($usedCodes);

            $usedPins[] = $pin;
            $usedCodes[] = $code;

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'pin' => $pin,
                    'verification_code' => $code,
                ]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('pin');
            $table->unique('verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['pin']);
            $table->dropUnique(['verification_code']);
            $table->dropColumn(['pin', 'verification_code']);
        });
    }

    /**
     * @param array<int, string> $usedPins
     */
    private function generateUniquePin(array $usedPins): string
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! in_array($pin, $usedPins, true)) {
                return $pin;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um PIN unico para os usuarios.');
    }

    /**
     * @param array<int, string> $usedCodes
     */
    private function generateUniqueVerificationCode(array $usedCodes): string
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $code = 'USR-'.Str::upper(Str::random(10));

            if (! in_array($code, $usedCodes, true)) {
                return $code;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um codigo unico para os usuarios.');
    }
};

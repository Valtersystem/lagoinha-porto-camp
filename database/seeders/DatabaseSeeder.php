<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserSex;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env(
            'ADMIN_PASSWORD',
            app()->environment(['local', 'testing']) ? 'password' : null,
        );

        if (blank($adminPassword)) {
            throw new RuntimeException('Defina ADMIN_PASSWORD antes de executar o seed fora do ambiente local.');
        }

        $adminRole = Role::firstOrCreateFor(UserRole::Administrator);
        $participantRole = Role::firstOrCreateFor(UserRole::Participant);

        User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $participantRole->id]);

        $admin = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'phone' => env('ADMIN_PHONE', '+351 900 000 001'),
                'sex' => env('ADMIN_SEX', UserSex::Male->value),
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ],
        );

        $admin->forceFill([
            'role_id' => $adminRole->id,
            'phone' => $admin->phone ?? env('ADMIN_PHONE', '+351 900 000 001'),
            'sex' => $admin->sex?->value ?? env('ADMIN_SEX', UserSex::Male->value),
        ])->save();

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserSex;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::firstOrCreateFor(UserRole::Administrator);
        $participantRole = Role::firstOrCreateFor(UserRole::Participant);

        User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $participantRole->id]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'phone' => '+351 900 000 001',
                'sex' => UserSex::Male->value,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->forceFill([
            'role_id' => $adminRole->id,
            'phone' => $admin->phone ?? '+351 900 000 001',
            'sex' => $admin->sex?->value ?? UserSex::Male->value,
        ])->save();

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}

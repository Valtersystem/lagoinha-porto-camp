<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::query()->updateOrCreate(
                ['key' => $role->value],
                [
                    'name' => $role->label(),
                    'description' => $role->description(),
                    'permissions' => $role->permissions(),
                    'is_system' => true,
                ],
            );
        }
    }
}

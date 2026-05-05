<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserSex;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'sex' => fake()->randomElement([UserSex::Male->value, UserSex::Female->value]),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => Role::query()->where('key', UserRole::default()->value)->value('id'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrator(): static
    {
        return $this->withRole(UserRole::Administrator);
    }

    public function monitor(): static
    {
        return $this->withRole(UserRole::Monitor);
    }

    public function teamLeader(): static
    {
        return $this->withRole(UserRole::TeamLeader);
    }

    public function participant(): static
    {
        return $this->withRole(UserRole::Participant);
    }

    private function withRole(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreateFor($role)->id,
        ]);
    }
}

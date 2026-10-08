<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Pembuat data user palsu untuk keperluan unit test.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nama_user' => fake()->name(),
            'username' => fake()->unique()->bothify('user_####??'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function operator(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_OPERATOR]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_MANAGER]);
    }
}

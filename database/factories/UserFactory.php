<?php

namespace Database\Factories;

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
            'npp' => fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'email' => fake()->boolean() ? fake()->unique()->safeEmail() : null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'aktif' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'remember_token' => Str::random(10),
        ];
    }
}

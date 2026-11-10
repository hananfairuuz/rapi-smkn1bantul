<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pabrik Data Palsu Pengguna (User Factory)
 *
 * Fungsi: Menghasilkan data pengguna tiruan secara otomatis untuk keperluan testing unit (Unit Test)
 * dan pengisian database simulasi tanpa perlu mengetik satu per satu.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Kata sandi default hash yang digunakan oleh data tiruan.
     */
    protected static ?string $password;

    /**
     * Definisi atribut bawaan setiap pengguna palsu.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State khusus: Pengguna yang belum memverifikasi email.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Daerah;
use Illuminate\Database\Eloquent\Factories\Factory;

class PesertaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'noka' => $this->faker->unique()->numerify('#############'),
            'nama' => $this->faker->name(),
            'no_hp' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'alamat' => $this->faker->address(),
            'status_aktif' => 'AKTIF',
            'nopendaftar' => $this->faker->numerify('#############'),
            'nopenghubung' => $this->faker->numerify('#############'),
            'daerah_id' => Daerah::factory(),
        ];
    }
}

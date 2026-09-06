<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DaerahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_dati2' => $this->faker->unique()->numerify('####'),
            'nama' => $this->faker->unique()->city(),
        ];
    }
}

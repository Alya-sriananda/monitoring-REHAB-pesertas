<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tanggal_data' => $this->faker->date(),
            'nama_file' => 'rehab_'.$this->faker->date('Ymd_His').'.xlsx',
            'jumlah_data' => $this->faker->numberBetween(10, 100),
            'imported_by' => User::factory(),
            'catatan' => $this->faker->sentence(),
        ];
    }
}

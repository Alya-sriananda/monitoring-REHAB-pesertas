<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Factories\Factory;

class PesertaBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'peserta_id' => Peserta::factory(),
            'data_source' => 'excel',
        ];
    }
}

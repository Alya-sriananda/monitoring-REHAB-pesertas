<?php

namespace Database\Factories;

use App\Models\RehabCaseMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class RehabInstallmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rehab_case_member_id' => RehabCaseMember::factory(),
            'nomor_cicilan' => $this->faker->numberBetween(1, 24),
            'periode_bulan' => $this->faker->date('Y-m-d', 'first day of this month'),
            'besaran_cicilan' => $this->faker->numberBetween(50000, 200000),
            'tanggal_bayar' => $this->faker->boolean(70) ? $this->faker->date() : null,
        ];
    }
}

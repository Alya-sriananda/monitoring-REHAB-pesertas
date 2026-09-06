<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\RehabCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SippVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rehab_case_id' => RehabCase::factory(),
            'batch_id' => Batch::factory(),
            'user_id' => User::factory(),
            'tanggal_cek' => $this->faker->date(),
            'terdaftar_rehab' => true,
            'status_rehab' => 'AKTIF',
            'id_cicilan' => $this->faker->uuid(),
            'noka_pendaftar' => $this->faker->numerify('#############'),
            'npp_petugas' => $this->faker->numerify('######'),
            'tanggal_daftar_rehab' => $this->faker->date(),
            'total_cicilan_bulan_ini' => $this->faker->numberBetween(100000, 500000),
            'sisa_tunggakan_sipp' => $this->faker->numberBetween(1000000, 5000000),
            'tanggal_akhir_cicilan' => $this->faker->date(),
            'jumlah_peserta_sipp' => $this->faker->numberBetween(1, 5),
            'catatan' => $this->faker->sentence(),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Solok',
            'email' => 'admin@bpjs-kesehatan.go.id',
            'npp' => '123456',
            'role' => 'admin',
            'aktif' => true,
            'must_change_password' => false,
            'password' => Hash::make('PasswordAwal123!'),
        ]);

        $daerahs = Daerah::factory(5)->create();
        $batch = Batch::factory()->create(['imported_by' => $admin->id]);

        foreach ($daerahs as $daerah) {
            $pesertas = Peserta::factory(3)->create(['daerah_id' => $daerah->id]);

            foreach ($pesertas as $peserta) {
                $case = RehabCase::factory()->create([
                    'peserta_id' => $peserta->id,
                    'created_from_batch_id' => $batch->id,
                    'npp_petugas' => $admin->npp,
                ]);

                $member = RehabCaseMember::factory()->create([
                    'rehab_case_id' => $case->id,
                    'peserta_id' => $peserta->id,
                    'is_pendaftar' => true,
                    'tagihan_awal' => 1000000,
                ]);

                RehabInstallment::factory(5)->create([
                    'rehab_case_member_id' => $member->id,
                    'besaran_cicilan' => 200000,
                ]);

                // Initial recalculation trigger
                $member->recalculateSisaTunggakan();
            }
        }
    }
}

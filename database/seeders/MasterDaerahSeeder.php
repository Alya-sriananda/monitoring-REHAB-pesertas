<?php

namespace Database\Seeders;

use App\Models\Daerah;
use Illuminate\Database\Seeder;

class MasterDaerahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $daerahs = [
            ['nama' => 'KAB. DHARMASRAYA', 'kode_dati2' => '0142'],
            ['nama' => 'KAB. SOLOK', 'kode_dati2' => '0134'],
            ['nama' => 'KOTA SAWAHLUNTO', 'kode_dati2' => '0173'],
            ['nama' => 'KAB. SOLOK SELATAN', 'kode_dati2' => '0143'],
            ['nama' => 'KAB. SIJUNJUNG', 'kode_dati2' => '0135'],
            ['nama' => 'KOTA SOLOK', 'kode_dati2' => '0172'],
        ];

        foreach ($daerahs as $daerah) {
            Daerah::updateOrCreate(
                ['nama' => $daerah['nama']],
                ['kode_dati2' => $daerah['kode_dati2']]
            );
        }
    }
}

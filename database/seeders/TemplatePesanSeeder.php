<?php

namespace Database\Seeders;

use App\Models\TemplatePesan;
use Illuminate\Database\Seeder;

class TemplatePesanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'nama_template' => 'Ada Tunggakan',
                'isi_template' => "Yth. Bapak/Ibu {nama},\n\nKami mengingatkan terkait pembayaran REHAB BPJS Kesehatan (NOKA: {noka}) untuk periode {periode} sebesar {nominal}.\n\nMohon dapat melakukan pembayaran sesuai ketentuan yang berlaku agar kepesertaan tetap aktif.\n\nTerima kasih.",
                'aktif' => true,
            ],
            [
                'nama_template' => 'Sudah Bayar Bulan Berjalan',
                'isi_template' => "Yth. Bapak/Ibu {nama},\n\nKami menginformasikan bahwa pembayaran REHAB BPJS Kesehatan (NOKA: {noka}) untuk periode {periode} telah tercatat sebesar {nominal}.\n\nTerima kasih atas pembayarannya.",
                'aktif' => true,
            ],
        ];

        foreach ($templates as $template) {
            TemplatePesan::updateOrCreate(
                ['nama_template' => $template['nama_template']],
                $template
            );
        }
    }
}

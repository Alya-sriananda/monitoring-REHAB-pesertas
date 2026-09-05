<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SippVerification extends Model
{
    protected $fillable = [
        'rehab_case_id',
        'batch_id',
        'user_id',
        'tanggal_cek',
        'terdaftar_rehab',
        'status_rehab',
        'id_cicilan',
        'noka_pendaftar',
        'npp_petugas',
        'tanggal_daftar_rehab',
        'total_cicilan_bulan_ini',
        'sisa_tunggakan_sipp',
        'tanggal_akhir_cicilan',
        'jumlah_peserta_sipp',
        'catatan',
    ];

    protected $casts = [
        'tanggal_cek' => 'date',
        'terdaftar_rehab' => 'boolean',
        'tanggal_daftar_rehab' => 'date',
        'tanggal_akhir_cicilan' => 'date',
    ];

    public function case()
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

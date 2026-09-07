<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SippVerification extends Model
{
    use HasFactory;

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
        'tagihan_bulan_berjalan',
        'tagihan_sebelum_bulan_berjalan',
        'status_pembayaran_bulan_berjalan',
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

    public function case(): BelongsTo
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

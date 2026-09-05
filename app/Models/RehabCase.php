<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RehabCase extends Model
{
    protected $fillable = [
        'peserta_id',
        'id_cicilan',
        'noka_pendaftar',
        'npp_petugas',
        'tanggal_pendaftaran',
        'jumlah_bulan_cicilan',
        'tanggal_akhir_cicilan',
        'status_rehab',
        'created_from_batch_id',
        'closed_at',
    ];

    protected $casts = [
        'tanggal_pendaftaran' => 'date',
        'tanggal_akhir_cicilan' => 'date',
        'closed_at' => 'date',
    ];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'created_from_batch_id');
    }

    public function members()
    {
        return $this->hasMany(RehabCaseMember::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RehabCaseMember extends Model
{
    protected $fillable = [
        'rehab_case_id',
        'peserta_id',
        'is_pendaftar',
        'tagihan_awal',
        'jml_bulan_menunggak_awal',
        'cicilan_bulanan',
        'data_source',
    ];

    protected $casts = [
        'is_pendaftar' => 'boolean',
    ];

    public function case()
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }

    public function installments()
    {
        return $this->hasMany(RehabInstallment::class);
    }
}

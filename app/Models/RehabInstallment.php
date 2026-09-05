<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RehabInstallment extends Model
{
    protected $fillable = [
        'rehab_case_member_id',
        'nomor_cicilan',
        'periode_bulan',
        'besaran_cicilan',
        'tanggal_bayar',
    ];

    protected $casts = [
        'periode_bulan' => 'date',
        'tanggal_bayar' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(RehabCaseMember::class, 'rehab_case_member_id');
    }
}

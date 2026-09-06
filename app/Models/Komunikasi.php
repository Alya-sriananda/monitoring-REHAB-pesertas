<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komunikasi extends Model
{
    protected $fillable = [
        'rehab_case_id',
        'peserta_id',
        'user_id',
        'template_pesan_id',
        'no_hp',
        'periode_bulan',
        'template',
        'pesan',
        'status',
        'tanggal_dihubungi',
        'catatan',
    ];

    protected $casts = [
        'periode_bulan' => 'date',
        'tanggal_dihubungi' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function templatePesan()
    {
        return $this->belongsTo(TemplatePesan::class, 'template_pesan_id');
    }
}

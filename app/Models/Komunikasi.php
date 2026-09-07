<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Komunikasi extends Model
{
    use HasFactory;

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

    public function case(): BelongsTo
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function templatePesan(): BelongsTo
    {
        return $this->belongsTo(TemplatePesan::class, 'template_pesan_id');
    }
}

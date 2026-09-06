<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RehabInstallment extends Model
{
    use HasFactory;

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

    public function member(): BelongsTo
    {
        return $this->belongsTo(RehabCaseMember::class, 'rehab_case_member_id');
    }

    protected static function booted()
    {
        static::saved(function ($installment) {
            $installment->member->recalculateSisaTunggakan();
        });

        static::deleted(function ($installment) {
            $installment->member->recalculateSisaTunggakan();
        });
    }
}

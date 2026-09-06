<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RehabCaseMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'rehab_case_id',
        'peserta_id',
        'is_pendaftar',
        'tagihan_awal',
        'sisa_tunggakan',
        'jml_bulan_menunggak_awal',
        'cicilan_bulanan',
        'data_source',
    ];

    protected $casts = [
        'is_pendaftar' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(RehabCase::class, 'rehab_case_id');
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(RehabInstallment::class);
    }

    public function recalculateSisaTunggakan()
    {
        $paidAmount = $this->installments()->whereNotNull('tanggal_bayar')->sum('besaran_cicilan');
        $this->sisa_tunggakan = max(0, $this->tagihan_awal - $paidAmount);
        $this->save();

        if ($this->case) {
            $this->case->recalculateSisaTunggakan();
        }
    }
}

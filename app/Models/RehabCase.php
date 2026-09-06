<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RehabCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'peserta_id',
        'id_cicilan',
        'noka_pendaftar',
        'npp_petugas',
        'tanggal_pendaftaran',
        'jumlah_bulan_cicilan',
        'sisa_tunggakan',
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

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'created_from_batch_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(RehabCaseMember::class);
    }

    public function recalculateSisaTunggakan()
    {
        $this->sisa_tunggakan = $this->members()->sum('sisa_tunggakan');
        $this->save();
    }
}

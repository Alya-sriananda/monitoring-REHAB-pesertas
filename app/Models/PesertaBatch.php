<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'peserta_id',
        'data_source',
        'statusaktif', 'total_peserta', 'zero', 'alamat', 'bulan_menunggak',
        'cabang_kd', 'divre_kd', 'email', 'endcicilan', 'idcicilan',
        'index_data', 'jmlbulancicilawal', 'jmlbulanmenunggakawal',
        'kanal_pendaftaran', 'kantor_cabang', 'kddati2', 'kddesa',
        'kdkec', 'kelas', 'kelas_group', 'namaentitas', 'nmdati2',
        'nmdesa', 'nmkc', 'nmkec', 'noentitas', 'nohp', 'nopendaftar',
        'nopenghubung', 'startcicilan', 'tanggalupdatedata', 'tglcicilan',
        'tottagbulanberjalanawal', 'tottagmenunggakawal',
        'tottagsdbulaniniawal', 'user_sipp',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}

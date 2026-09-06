<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_data',
        'nama_file',
        'file_hash',
        'jumlah_data',
        'jumlah_row_asli',
        'jumlah_row_valid',
        'jumlah_duplicate',
        'jumlah_conflict',
        'jumlah_invalid',
        'jumlah_peserta_baru',
        'jumlah_peserta_diperbarui',
        'imported_by',
        'status_proses',
        'import_errors',
        'catatan',
    ];

    protected $casts = [
        'tanggal_data' => 'date',
    ];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

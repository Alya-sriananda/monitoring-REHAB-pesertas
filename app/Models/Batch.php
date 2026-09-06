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
        'jumlah_data',
        'imported_by',
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

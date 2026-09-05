<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
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

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

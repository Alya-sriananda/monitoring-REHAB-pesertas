<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Daerah extends Model
{
    use HasFactory;

    protected $table = 'daerah';

    protected $fillable = [
        'kode_dati2',
        'nama',
    ];

    public function pesertas()
    {
        return $this->hasMany(Peserta::class, 'daerah_id');
    }
}

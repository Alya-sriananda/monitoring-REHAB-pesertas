<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Daerah extends Model
{
    protected $table = 'daerah';

    protected $fillable = [
        'kode_dati2',
        'nama',
    ];
}

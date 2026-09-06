<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplatePesan extends Model
{
    protected $fillable = [
        'nama_template',
        'isi_template',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}

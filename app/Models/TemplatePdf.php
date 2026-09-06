<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplatePdf extends Model
{
    protected $fillable = [
        'nama_template',
        'judul',
        'subjudul',
        'header_text',
        'footer_text',
        'field_config',
        'aktif',
    ];

    protected $casts = [
        'field_config' => 'array',
        'aktif' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peserta extends Model
{
    use HasFactory;

    protected $fillable = [
        'noka',
        'nama',
        'no_hp',
        'email',
        'alamat',
        'status_aktif',
        'nopendaftar',
        'nopenghubung',
        'daerah_id',
    ];

    public function daerah(): BelongsTo
    {
        return $this->belongsTo(Daerah::class);
    }

    public function rehabCases(): HasMany
    {
        return $this->hasMany(RehabCase::class);
    }

    public function rehabCaseMembers(): HasMany
    {
        return $this->hasMany(RehabCaseMember::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(PesertaBatch::class);
    }
}

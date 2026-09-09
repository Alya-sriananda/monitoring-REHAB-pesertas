<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function sippVerifications(): HasMany
    {
        return $this->hasMany(SippVerification::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(PesertaBatch::class);
    }

    public function scopeForWorkQueue($query, Batch $batch)
    {
        $cutoffDate = Carbon::parse($batch->tanggal_data)->startOfMonth()->toDateString();
        $cutoffDateEnd = $cutoffDate.' 23:59:59';

        return $query->where(function ($q) use ($batch, $cutoffDateEnd) {
            // 1. Peserta in the current batch
            $q->whereHas('batches', function ($b) use ($batch) {
                $b->where('batch_id', $batch->id);
            })
            // 2. OR Peserta from past batches with ACTIVE rehab and unpaid installments up to current batch period
                ->orWhereHas('rehabCases', function ($rc) use ($cutoffDateEnd) {
                    $rc->where('status_rehab', 'AKTIF')
                        ->whereHas('members.installments', function ($inst) use ($cutoffDateEnd) {
                            $inst->whereNull('tanggal_bayar')
                                ->where('periode_bulan', '<=', $cutoffDateEnd);
                        });
                });
        });
    }

    public function scopeWithTunggakanStats($query, Batch $batch)
    {
        $cutoffDate = Carbon::parse($batch->tanggal_data)->startOfMonth()->toDateString().' 23:59:59';

        return $query->addSelect([
            'jumlah_bulan_menunggak' => RehabInstallment::selectRaw('count(*)')
                ->join('rehab_case_members', 'rehab_installments.rehab_case_member_id', '=', 'rehab_case_members.id')
                ->join('rehab_cases', 'rehab_case_members.rehab_case_id', '=', 'rehab_cases.id')
                ->whereColumn('rehab_cases.peserta_id', 'pesertas.id')
                ->where('rehab_cases.status_rehab', 'AKTIF')
                ->whereNull('rehab_installments.tanggal_bayar')
                ->where('rehab_installments.periode_bulan', '<=', $cutoffDate),

            'sisa_tunggakan' => RehabInstallment::selectRaw('COALESCE(SUM(besaran_cicilan), 0)')
                ->join('rehab_case_members', 'rehab_installments.rehab_case_member_id', '=', 'rehab_case_members.id')
                ->join('rehab_cases', 'rehab_case_members.rehab_case_id', '=', 'rehab_cases.id')
                ->whereColumn('rehab_cases.peserta_id', 'pesertas.id')
                ->where('rehab_cases.status_rehab', 'AKTIF')
                ->whereNull('rehab_installments.tanggal_bayar')
                ->where('rehab_installments.periode_bulan', '<=', $cutoffDate),
        ]);
    }

    public function scopeWithStatusProses($query, Batch $batch)
    {
        $cutoffDate = Carbon::parse($batch->tanggal_data)->startOfMonth()->toDateString();

        // Using DB::raw directly with inline bindings since addSelect doesn't support an array of bindings directly
        return $query->addSelect(DB::raw($this->getStatusProsesCaseSql($batch, $cutoffDate).' as status_proses'));
    }

    public function scopeWhereStatusProses($query, Batch $batch, string $status)
    {
        $cutoffDate = Carbon::parse($batch->tanggal_data)->startOfMonth()->toDateString();

        return $query->whereRaw('('.$this->getStatusProsesCaseSql($batch, $cutoffDate).') = ?', [$status]);
    }

    private function getStatusProsesCaseSql(Batch $batch, string $cutoffDate): string
    {
        return "
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM komunikasis k
                    INNER JOIN rehab_cases rc ON k.rehab_case_id = rc.id
                    WHERE rc.peserta_id = pesertas.id 
                      AND k.periode_bulan LIKE '{$cutoffDate}%'
                      AND k.status = 'sudah_dihubungi'
                ) THEN 'SUDAH DIHUBUNGI'
                WHEN EXISTS (
                    SELECT 1 FROM rehab_installments ri
                    INNER JOIN rehab_case_members rcm ON ri.rehab_case_member_id = rcm.id
                    INNER JOIN rehab_cases rc ON rcm.rehab_case_id = rc.id
                    WHERE rc.peserta_id = pesertas.id
                      AND rc.status_rehab = 'AKTIF'
                      AND ri.tanggal_bayar IS NULL
                      AND ri.periode_bulan <= '{$cutoffDate} 23:59:59'
                ) THEN 'PERLU FOLLOW-UP'
                WHEN EXISTS (
                    SELECT 1 FROM sipp_verifications sv
                    WHERE sv.peserta_id = pesertas.id
                      AND sv.batch_id = {$batch->id}
                      AND sv.terdaftar_rehab = 1
                ) THEN 'TERVERIFIKASI / REHAB'
                WHEN EXISTS (
                    SELECT 1 FROM sipp_verifications sv
                    WHERE sv.peserta_id = pesertas.id
                      AND sv.batch_id = {$batch->id}
                      AND sv.terdaftar_rehab = 0
                ) THEN 'TERVERIFIKASI / NON-REHAB'
                ELSE 'BELUM DIVERIFIKASI'
            END
        ";
    }
}

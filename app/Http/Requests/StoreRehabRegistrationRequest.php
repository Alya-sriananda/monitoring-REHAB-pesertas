<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRehabRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We'll rely on controller/middleware auth
    }

    public function rules(): array
    {
        return [
            // SIPP Verification Snapshot
            'sipp_tanggal_cek' => ['required', 'date'],
            'sipp_terdaftar_rehab' => ['required', 'boolean'],
            'sipp_status_rehab' => ['nullable', 'string', 'max:50'],
            'sipp_id_cicilan' => ['nullable', 'string', 'max:100'],
            'sipp_noka_pendaftar' => ['nullable', 'string', 'max:30'],
            'sipp_npp_petugas' => ['nullable', 'string', 'max:50'],
            'sipp_tanggal_daftar_rehab' => ['nullable', 'date'],
            'sipp_tagihan_bulan_berjalan' => ['nullable', 'numeric', 'min:0'],
            'sipp_tagihan_sebelum_bulan_berjalan' => ['nullable', 'numeric', 'min:0'],
            'sipp_status_pembayaran_bulan_berjalan' => ['nullable', 'string', 'max:50'],
            'sipp_tanggal_akhir_cicilan' => ['nullable', 'date'],
            'sipp_jumlah_peserta_sipp' => ['nullable', 'integer', 'min:1'],
            'sipp_catatan' => ['nullable', 'string'],

            // Financial & Case
            'tanggal_pendaftaran' => ['required', 'date'],
            'jumlah_bulan_cicilan' => ['required', 'integer', 'min:1'],

            // Members
            'members' => ['required', 'array', 'min:1'],
            'members.*.peserta_id' => ['required', 'exists:pesertas,id'],
            'members.*.tagihan_awal' => ['required', 'numeric', 'min:1'],
            'members.*.is_pendaftar' => ['boolean'],
            'members.*.jml_bulan_menunggak_awal' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

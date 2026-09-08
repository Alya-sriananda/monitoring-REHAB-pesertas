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
            'sipp_terdaftar_rehab' => ['required', 'boolean'],
            'sipp_id_cicilan' => ['nullable', 'string', 'max:100'],
            'sipp_noka_pendaftar' => ['nullable', 'string', 'max:30'],
            'sipp_npp_petugas' => ['nullable', 'string', 'max:50'],
            'sipp_tanggal_daftar_rehab' => ['nullable', 'date'],
            'sipp_tanggal_akhir_cicilan' => ['nullable', 'date'],
            'sipp_jumlah_peserta_sipp' => ['nullable', 'integer', 'min:1'],
            'sipp_catatan' => ['nullable', 'string'],

            // Financial & Case
            'tanggal_pendaftaran' => ['required_if:sipp_terdaftar_rehab,true', 'nullable', 'date'],
            'jumlah_bulan_cicilan' => ['required_if:sipp_terdaftar_rehab,true', 'nullable', 'integer', 'min:1'],

            // Members
            'members' => ['required_if:sipp_terdaftar_rehab,true', 'nullable', 'array', 'min:1'],
            'members.*.peserta_id' => ['nullable', 'exists:pesertas,id'],
            'members.*.nama' => ['required_without:members.*.peserta_id', 'nullable', 'string', 'max:255'],
            'members.*.noka' => ['required_without:members.*.peserta_id', 'nullable', 'string', 'max:50'],
            'members.*.tagihan_awal' => ['required_if:sipp_terdaftar_rehab,true', 'nullable', 'numeric', 'min:1'],
            'members.*.is_pendaftar' => ['boolean'],
            'members.*.jml_bulan_menunggak_awal' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

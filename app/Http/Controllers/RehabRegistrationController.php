<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRehabRegistrationRequest;
use App\Models\Peserta;
use App\Models\SippVerification;
use App\Services\RehabCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RehabRegistrationController extends Controller
{
    protected RehabCaseService $rehabCaseService;

    public function __construct(RehabCaseService $rehabCaseService)
    {
        $this->rehabCaseService = $rehabCaseService;
    }

    public function store(StoreRehabRegistrationRequest $request, Peserta $peserta): RedirectResponse
    {
        // 1. Protect against overwriting existing ACTIVE cases.
        // A Peserta shouldn't be added to a new active case if they already have one.
        // Also check for the candidate members.
        $memberIds = collect($request->input('members'))->pluck('peserta_id')->toArray();
        $existingActiveCasesCount = DB::table('rehab_case_members')
            ->join('rehab_cases', 'rehab_cases.id', '=', 'rehab_case_members.rehab_case_id')
            ->whereIn('rehab_case_members.peserta_id', $memberIds)
            ->where('rehab_cases.status_rehab', 'AKTIF')
            ->count();

        if ($existingActiveCasesCount > 0) {
            return back()->withErrors([
                'members' => 'Salah satu kandidat yang dipilih sudah memiliki kasus REHAB yang aktif. Pendaftaran ditolak untuk mencegah duplikasi aktif.',
            ]);
        }

        // 2. Wrap in a single transaction
        DB::transaction(function () use ($request, $peserta) {
            // A. Create RehabCase and Members via Service
            $caseData = [
                'peserta_id' => $peserta->id, // Head of the manual case creation
                'id_cicilan' => $request->input('sipp_id_cicilan'),
                'noka_pendaftar' => $request->input('sipp_noka_pendaftar') ?: $peserta->noka,
                'npp_petugas' => $request->input('sipp_npp_petugas'),
                'tanggal_pendaftaran' => $request->input('tanggal_pendaftaran'),
                'jumlah_bulan_cicilan' => $request->input('jumlah_bulan_cicilan'),
                'tanggal_akhir_cicilan' => $request->input('sipp_tanggal_akhir_cicilan'),
                'status_rehab' => 'AKTIF', // Default active on registration
            ];

            $rehabCase = $this->rehabCaseService->createCaseWithMembers($caseData, $request->input('members'));

            // B. Create SIPP Verification Snapshot
            SippVerification::create([
                'rehab_case_id' => $rehabCase->id,
                'user_id' => auth()->id(),
                'tanggal_cek' => $request->input('sipp_tanggal_cek'),
                'terdaftar_rehab' => $request->input('sipp_terdaftar_rehab'),
                'status_rehab' => $request->input('sipp_status_rehab'),
                'id_cicilan' => $request->input('sipp_id_cicilan'),
                'noka_pendaftar' => $request->input('sipp_noka_pendaftar'),
                'npp_petugas' => $request->input('sipp_npp_petugas'),
                'tanggal_daftar_rehab' => $request->input('sipp_tanggal_daftar_rehab'),
                'tagihan_bulan_berjalan' => $request->input('sipp_tagihan_bulan_berjalan'),
                'tagihan_sebelum_bulan_berjalan' => $request->input('sipp_tagihan_sebelum_bulan_berjalan'),
                'status_pembayaran_bulan_berjalan' => $request->input('sipp_status_pembayaran_bulan_berjalan'),
                'tanggal_akhir_cicilan' => $request->input('sipp_tanggal_akhir_cicilan'),
                'jumlah_peserta_sipp' => $request->input('sipp_jumlah_peserta_sipp'),
                'catatan' => $request->input('sipp_catatan'),
            ]);
        });

        return redirect()->route('peserta.show', $peserta)->with('success', 'Verifikasi SIPP dan Kasus REHAB berhasil didaftarkan.');
    }
}

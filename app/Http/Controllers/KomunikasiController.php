<?php

namespace App\Http\Controllers;

use App\Models\Komunikasi;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\TemplatePesan;
use App\Services\TemplatePesanService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KomunikasiController extends Controller
{
    /**
     * Generate message preview based on template and context.
     */
    public function generatePreview(Request $request, TemplatePesanService $service)
    {
        $validated = $request->validate([
            'template_pesan_id' => 'required|exists:template_pesans,id',
            'peserta_id' => 'required|exists:pesertas,id',
            'periode_bulan' => 'required|date',
            'nominal' => 'required|numeric',
        ]);

        $template = TemplatePesan::findOrFail($validated['template_pesan_id']);
        $peserta = Peserta::findOrFail($validated['peserta_id']);
        $periode = Carbon::parse($validated['periode_bulan'])->translatedFormat('F Y');

        $context = [
            'nama' => $peserta->nama,
            'noka' => $peserta->noka,
            'periode' => $periode,
            'nominal' => $validated['nominal'],
        ];

        $pesan = $service->render($template, $context);

        return response()->json([
            'pesan' => $pesan,
        ]);
    }

    /**
     * Store the communication record.
     */
    public function store(Request $request, RehabCase $rehabCase)
    {
        $validated = $request->validate([
            'peserta_id' => 'required|exists:pesertas,id',
            'template_pesan_id' => 'required|exists:template_pesans,id',
            'periode_bulan' => 'required|date',
            'pesan' => 'required|string',
            'status' => 'required|in:sudah_dihubungi,tidak_terdaftar_wa,gagal',
            'catatan' => 'nullable|string',
        ]);

        $peserta = Peserta::findOrFail($validated['peserta_id']);
        $template = TemplatePesan::findOrFail($validated['template_pesan_id']);
        $periodeDate = Carbon::parse($validated['periode_bulan'])->startOfMonth();

        // Prevent exact duplicates if needed, but per specs:
        // "Jangan membuat duplicate communication record karena: refresh halaman, double click, request retry"
        // Let's use firstOrCreate or check if it exists recently.
        $existing = Komunikasi::where('rehab_case_id', $rehabCase->id)
            ->where('periode_bulan', $periodeDate)
            ->where('status', $validated['status'])
            ->where('template_pesan_id', $validated['template_pesan_id'])
            ->where('created_at', '>=', now()->subMinutes(5)) // simple debounce
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Komunikasi sudah dicatat baru-baru ini.'], 200);
        }

        $komunikasi = Komunikasi::create([
            'rehab_case_id' => $rehabCase->id,
            'peserta_id' => $peserta->id,
            'user_id' => $request->user()->id,
            'template_pesan_id' => $template->id,
            'no_hp' => $peserta->no_hp ?? '',
            'periode_bulan' => $periodeDate,
            'template' => $template->nama_template,
            'pesan' => $validated['pesan'],
            'status' => $validated['status'],
            'tanggal_dihubungi' => now(),
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Komunikasi berhasil dicatat',
            'komunikasi' => $komunikasi,
        ], 201);
    }
}

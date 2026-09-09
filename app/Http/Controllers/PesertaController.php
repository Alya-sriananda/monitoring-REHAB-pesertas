<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PesertaController extends Controller
{
    public function index(Request $request)
    {
        $daerahs = Daerah::whereHas('pesertas')->orderBy('nama')->get();
        $batches = Batch::orderBy('tanggal_data', 'desc')->get(['id', 'nama_file', 'tanggal_data']);

        $latestBatch = $batches->first();
        $batchId = $request->input('batch_id');
        if (empty($batchId)) {
            $batchId = $latestBatch?->id;
        }

        if (! $batchId) {
            $pesertas = Peserta::whereRaw('1=0')->paginate(15);

            return Inertia::render('peserta/index', [
                'pesertas' => $pesertas,
                'filters' => [
                    'search' => $request->input('search'),
                    'daerah_id' => $request->input('daerah_id'),
                    'batch_id' => $batchId,
                    'status_rehab' => $request->input('status_rehab'),
                    'status_proses' => $request->input('status_proses'),
                ],
                'daerahs' => $daerahs,
                'batches' => $batches,
            ]);
        }

        $activeBatch = Batch::find($batchId);

        $query = Peserta::select('pesertas.*')
            ->with(['daerah', 'rehabCaseMembers.case', 'batches.batch'])
            ->forWorkQueue($activeBatch)
            ->withTunggakanStats($activeBatch)
            ->withStatusProses($activeBatch);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('noka', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('daerah_id')) {
            $query->where('daerah_id', $request->input('daerah_id'));
        }

        if ($request->filled('status_rehab')) {
            $status = $request->input('status_rehab');
            if ($status === 'ada') {
                $query->has('rehabCaseMembers');
            } elseif ($status === 'tidak') {
                $query->doesntHave('rehabCaseMembers');
            }
        }

        if ($request->filled('status_proses')) {
            $query->whereStatusProses($activeBatch, $request->input('status_proses'));
        }

        $query->orderBy('jumlah_bulan_menunggak', 'desc')
            ->orderBy('sisa_tunggakan', 'desc')
            ->orderBy('pesertas.updated_at', 'desc');

        $pesertas = $query->paginate(15)->withQueryString();

        return Inertia::render('peserta/index', [
            'pesertas' => $pesertas,
            'filters' => [
                'search' => $request->input('search'),
                'daerah_id' => $request->input('daerah_id'),
                'batch_id' => $batchId,
                'status_rehab' => $request->input('status_rehab'),
                'status_proses' => $request->input('status_proses'),
            ],
            'daerahs' => $daerahs,
            'batches' => $batches,
            'activeBatch' => $activeBatch,
        ]);
    }

    public function show(Peserta $peserta)
    {
        $peserta->load([
            'daerah',
            'batches.batch',
            'rehabCaseMembers.case.members.peserta',
            'rehabCaseMembers.installments',
            'rehabCaseMembers.case.members.installments' => function ($query) {
                $query->orderBy('periode_bulan');
            },
            'sippVerifications' => function ($query) {
                $query->orderBy('tanggal_cek', 'desc');
            },
        ]);

        $candidates = collect();
        if (! empty($peserta->no_hp)) {
            $candidates = Peserta::where('no_hp', $peserta->no_hp)
                ->where('id', '!=', $peserta->id)
                ->orderBy('nama')
                ->get();
        }

        $latestBatch = $peserta->batches->sortByDesc('created_at')->first();

        return Inertia::render('peserta/show', [
            'peserta' => $peserta,
            'candidates' => $candidates,
            'latestBatch' => $latestBatch,
        ]);
    }
}

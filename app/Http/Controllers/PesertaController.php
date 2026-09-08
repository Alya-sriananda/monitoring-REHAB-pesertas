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
        $query = Peserta::with(['daerah', 'rehabCaseMembers.case', 'batches.batch']);

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

        if ($request->filled('batch_id')) {
            $query->whereHas('batches', function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            });
        }

        if ($request->filled('status_rehab')) {
            $status = $request->input('status_rehab');
            if ($status === 'ada') {
                $query->has('rehabCaseMembers');
            } elseif ($status === 'tidak') {
                $query->doesntHave('rehabCaseMembers');
            }
        }

        $query->orderBy('updated_at', 'desc');

        $pesertas = $query->paginate(15)->withQueryString();

        $daerahs = Daerah::whereHas('pesertas')->orderBy('nama')->get();
        $batches = Batch::orderBy('tanggal_data', 'desc')->get(['id', 'nama_file', 'tanggal_data']);

        return Inertia::render('peserta/index', [
            'pesertas' => $pesertas,
            'filters' => $request->only(['search', 'daerah_id', 'batch_id', 'status_rehab']),
            'daerahs' => $daerahs,
            'batches' => $batches,
        ]);
    }

    public function show(Peserta $peserta)
    {
        $peserta->load([
            'daerah',
            'batches.batch',
            'rehabCaseMembers.case',
            'rehabCaseMembers.installments',
        ]);

        $candidates = collect();
        if (! empty($peserta->no_hp)) {
            $candidates = Peserta::where('no_hp', $peserta->no_hp)
                ->where('id', '!=', $peserta->id)
                ->orderBy('nama')
                ->get();
        }

        return Inertia::render('peserta/show', [
            'peserta' => $peserta,
            'candidates' => $candidates,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Services\BatchImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with('importer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('batches/index', [
            'batches' => $batches,
        ]);
    }

    public function show(Batch $batch)
    {
        $batch->load('importer');

        return Inertia::render('batches/show', [
            'batch' => $batch,
            'import_errors' => $batch->import_errors ? json_decode($batch->import_errors, true) : null,
        ]);
    }

    public function create()
    {
        return Inertia::render('batches/import');
    }

    public function preview(Request $request, BatchImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|extensions:xlsx,xls,csv|max:51200', // 50MB max
            'tanggal_data' => 'required|date',
        ]);

        $file = $request->file('file');
        // Save to a temporary private storage path
        $filename = Str::uuid().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('private/imports/temp', $filename);

        try {
            $stats = $importService->preview($path);

            if (isset($stats['status']) && $stats['status'] === 'rejected') {
                // Delete temp file if rejected
                Storage::delete($path);

                return response()->json([
                    'error' => $stats['message'],
                ], 422);
            }

            return response()->json([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Storage::delete($path);

            return response()->json([
                'error' => 'Gagal membaca file: '.$e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request, BatchImportService $importService)
    {
        $request->validate([
            'file_path' => 'required|string',
            'original_name' => 'required|string',
            'tanggal_data' => 'required|date',
        ]);

        $path = $request->input('file_path');

        if (! Storage::exists($path)) {
            return back()->withErrors(['error' => 'File temporary tidak ditemukan. Silakan upload ulang.']);
        }

        try {
            $metadata = [
                'tanggal_data' => $request->input('tanggal_data'),
                'nama_file' => $request->input('original_name'),
            ];

            $batch = $importService->import($path, $metadata);

            // Clean up temporary file
            Storage::delete($path);

            return redirect()->route('batches.show', $batch->id)
                ->with('success', 'Import data berhasil diselesaikan.');

        } catch (\Exception $e) {
            // Do not delete file if transaction fails so it can be debugged, or delete it?
            // Safer to delete to avoid orphaned files, but let's delete it anyway
            Storage::delete($path);

            return back()->withErrors(['error' => 'Gagal melakukan import: '.$e->getMessage()]);
        }
    }
}

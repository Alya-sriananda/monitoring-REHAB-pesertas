<?php

namespace App\Http\Controllers;

use App\Models\TemplatePesan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TemplateSettingsController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-templates');

        return Inertia::render('settings/templates', [
            'templatePesans' => TemplatePesan::orderBy('nama_template')->get(),
        ]);
    }

    public function storePesan(Request $request)
    {
        Gate::authorize('manage-templates');

        $validated = $request->validate([
            'nama_template' => 'required|string|max:150',
            'isi_template' => 'required|string',
            'aktif' => 'boolean',
        ]);

        TemplatePesan::create($validated);

        return back()->with('success', 'Template Pesan berhasil dibuat.');
    }

    public function updatePesan(Request $request, TemplatePesan $templatePesan)
    {
        Gate::authorize('manage-templates');

        $validated = $request->validate([
            'nama_template' => 'required|string|max:150',
            'isi_template' => 'required|string',
            'aktif' => 'boolean',
        ]);

        $templatePesan->update($validated);

        return back()->with('success', 'Template Pesan berhasil diperbarui.');
    }

    public function destroyPesan(TemplatePesan $templatePesan)
    {
        Gate::authorize('manage-templates');

        $templatePesan->delete();

        return back()->with('success', 'Template Pesan berhasil dihapus.');
    }
}

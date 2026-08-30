<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaManagementController extends Controller
{
    public function index(): View
    {
        $areas = AreaParkir::with('tarif')->orderBy('created_at', 'desc')->get();
        $tarifs = Tarif::orderBy('jenis_kendaraan')->get();

        return view('management.area.index', compact('areas', 'tarifs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'tarif_id' => ['nullable', 'uuid', 'exists:tarifs,id'],
        ]);

        AreaParkir::create($validated);

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil ditambahkan.');
    }

    public function update(Request $request, AreaParkir $areaParkir): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'tarif_id' => ['nullable', 'uuid', 'exists:tarifs,id'],
        ]);

        $areaParkir->update($validated);

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil diperbarui.');
    }

    public function destroy(AreaParkir $areaParkir): RedirectResponse
    {
        $areaParkir->delete();

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil dihapus.');
    }
}

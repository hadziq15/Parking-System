<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggan;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TarifManagementController extends Controller
{
    public function index(): View
    {
        $tarifs = Tarif::orderBy('created_at', 'desc')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('created_at', 'desc')->get();

        return view('management.tarif.index', compact('tarifs', 'jenisPelanggan'));
    }

    public function storeTarif(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kendaraan' => ['required', 'in:mobil,motor'],
            'tarif' => ['required', 'integer', 'min:0'],
        ]);

        Tarif::create($validated);

        return redirect()->route('management.tarif.index')->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function updateTarif(Request $request, Tarif $tarif): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kendaraan' => ['required', 'in:mobil,motor'],
            'tarif' => ['required', 'integer', 'min:0'],
        ]);

        $tarif->update($validated);

        return redirect()->route('management.tarif.index')->with('success', 'Tarif berhasil diperbarui.');
    }

    public function destroyTarif(Tarif $tarif): RedirectResponse
    {
        $tarif->delete();

        return redirect()->route('management.tarif.index')->with('success', 'Tarif berhasil dihapus.');
    }

    public function storeJenisPelanggan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_gratis_parkir' => ['nullable', 'boolean'],
            'is_bebas_denda' => ['nullable', 'boolean'],
            'prioritas_level' => ['required', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'denda' => ['nullable', 'boolean'],
        ]);

        JenisPelanggan::create($validated);

        return redirect()->route('management.tarif.index')->with('success', 'Jenis pelanggan berhasil ditambahkan.');
    }

    public function updateJenisPelanggan(Request $request, JenisPelanggan $jenisPelanggan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_gratis_parkir' => ['nullable', 'boolean'],
            'is_bebas_denda' => ['nullable', 'boolean'],
            'prioritas_level' => ['required', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'denda' => ['nullable', 'boolean'],
        ]);

        $jenisPelanggan->update($validated);

        return redirect()->route('management.tarif.index')->with('success', 'Jenis pelanggan berhasil diperbarui.');
    }

    public function destroyJenisPelanggan(JenisPelanggan $jenisPelanggan): RedirectResponse
    {
        $jenisPelanggan->delete();

        return redirect()->route('management.tarif.index')->with('success', 'Jenis pelanggan berhasil dihapus.');
    }
}

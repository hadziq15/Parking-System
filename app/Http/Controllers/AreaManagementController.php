<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\JenisPelanggan;
use App\Models\Log;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AreaManagementController extends Controller
{
    /**
     * Menampilkan daftar area parkir yang bisa dikelola oleh admin.
     * Data ini dipakai untuk form penambahan area, tarif, dan keterkaitan
     * dengan jenis pelanggan yang diperbolehkan masuk.
     */
    public function index(): View
    {
        $areas = AreaParkir::with(['tarif', 'jenisPelanggans'])->orderBy('created_at', 'desc')->get();
        $tarifs = Tarif::orderBy('jenis_kendaraan')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('nama')->get();

        return view('management.area.index', compact('areas', 'tarifs', 'jenisPelanggan'));
    }

    /**
     * Simpan area baru beserta relasi jenis pelanggan yang diizinkan.
     * Struktur ini memastikan konfigurasi parkir tetap konsisten sebelum
     * transaksi masuk dilakukan di area tersebut.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'tarif_id' => ['nullable', 'uuid', 'exists:tarifs,id'],
            'jenis_pelanggan_ids' => ['nullable', 'array'],
            'jenis_pelanggan_ids.*' => ['uuid', 'exists:jenis_pelanggans,id'],
        ]);

        $area = AreaParkir::create($validated);
        $area->jenisPelanggans()->sync($validated['jenis_pelanggan_ids'] ?? []);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Area parkir ditambahkan: '.$area->nama.' ('.$area->lokasi.')',
        ]);

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil ditambahkan.');
    }

    public function update(Request $request, AreaParkir $areaParkir): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'tarif_id' => ['nullable', 'uuid', 'exists:tarifs,id'],
            'jenis_pelanggan_ids' => ['nullable', 'array'],
            'jenis_pelanggan_ids.*' => ['uuid', 'exists:jenis_pelanggans,id'],
        ]);

        $areaParkir->update($validated);
        $areaParkir->jenisPelanggans()->sync($validated['jenis_pelanggan_ids'] ?? []);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Area parkir diperbarui: '.$areaParkir->nama.' ('.$areaParkir->lokasi.')',
        ]);

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil diperbarui.');
    }

    public function destroy(AreaParkir $areaParkir): RedirectResponse
    {
        $namaArea = $areaParkir->nama;
        $lokasiArea = $areaParkir->lokasi;

        $areaParkir->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Area parkir dihapus: '.$namaArea.' ('.$lokasiArea.')',
        ]);

        return redirect()->route('management.area.index')->with('success', 'Area parkir berhasil dihapus.');
    }
}

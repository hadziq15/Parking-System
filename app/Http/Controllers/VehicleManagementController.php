<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggan;
use App\Models\Kendaraan;
use App\Models\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehicleManagementController extends Controller
{
    /**
     * Menampilkan kendaraan yang sudah terdaftar serta daftar jenis pelanggan
     * untuk mempermudah pengelolaan data kendaraan di parkir.
     */
    public function index(): View
    {
        $vehicles = Kendaraan::with('jenisPelanggan')->orderBy('created_at', 'desc')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('created_at', 'desc')->get();

        return view('management.vehicle.index', compact('vehicles', 'jenisPelanggan'));
    }

    /**
     * Menyimpan data kendaraan baru. Validasi panjang nomor plat dan jenis
     * kendaraan dibuat agar data tetap konsisten dengan skema migrasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pemilik' => ['required', 'string', 'max:100'],
            'plat_nomor' => ['required', 'string', 'max:20', 'unique:kendaraans,plat_nomor'],
            'jenis_kendaraan' => ['required', 'in:mobil,motor,truk'],
            'warna' => ['required', 'string', 'max:30'],
            'jenis_pelanggan_id' => ['nullable', 'uuid', 'exists:jenis_pelanggans,id'],
        ]);

        $kendaraan = Kendaraan::create($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan ditambahkan: '.$kendaraan->plat_nomor.' ('.$kendaraan->pemilik.')',
        ]);

        return redirect()->route('management.vehicle.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function update(Request $request, Kendaraan $kendaraan): RedirectResponse
    {
        $validated = $request->validate([
            'pemilik' => ['required', 'string', 'max:100'],
            'plat_nomor' => ['required', 'string', 'max:20', 'unique:kendaraans,plat_nomor,'.$kendaraan->id],
            'jenis_kendaraan' => ['required', 'in:mobil,motor,truk'],
            'warna' => ['required', 'string', 'max:30'],
            'jenis_pelanggan_id' => ['nullable', 'uuid', 'exists:jenis_pelanggans,id'],
        ]);

        $kendaraan->update($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan diperbarui: '.$kendaraan->plat_nomor.' ('.$kendaraan->pemilik.')',
        ]);

        return redirect()->route('management.vehicle.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Kendaraan $kendaraan): RedirectResponse
    {
        $platNomor = $kendaraan->plat_nomor;
        $pemilik = $kendaraan->pemilik;

        $kendaraan->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan dihapus: '.$platNomor.' ('.$pemilik.')',
        ]);

        return redirect()->route('management.vehicle.index')->with('success', 'Kendaraan berhasil dihapus.');
    }
}

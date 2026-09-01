<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggan;
use App\Models\Log;
use App\Models\Setting;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TarifManagementController extends Controller
{
    /**
     * Menyiapkan data tarif, jenis pelanggan, dan pengaturan aplikasinya
     * untuk ditampilkan pada halaman manajemen tarif.
     */
    public function index(): View
    {
        $tarifs = Tarif::orderBy('created_at', 'desc')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('created_at', 'desc')->get();
        $settings = Setting::orderBy('key')->get()->keyBy('key');

        return view('management.tarif.index', compact('tarifs', 'jenisPelanggan', 'settings'));
    }

    /**
     * Menyimpan tarif baru dengan validasi jenis kendaraan dan nominal.
     * Nilai ini nanti dipakai saat perhitungan pembayaran parkir.
     */
    public function storeTarif(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kendaraan' => ['required', 'in:mobil,motor'],
            'tarif_jam_pertama' => ['required', 'integer', 'min:0'],
            'tarif_jam_berikutnya' => ['required', 'integer', 'min:0'],
        ]);

        $tarif = Tarif::create($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Tarif ditambahkan: '.$tarif->jenis_kendaraan.' | Rp '.$tarif->tarif_jam_pertama.' / jam pertama, Rp '.$tarif->tarif_jam_berikutnya.' / jam berikutnya',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Tarif berhasil ditambahkan.')
            ->with('active_tab', $request->input('active_tab', 'tarif'));
    }

    public function updateTarif(Request $request, Tarif $tarif): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kendaraan' => ['required', 'in:mobil,motor'],
            'tarif_jam_pertama' => ['required', 'integer', 'min:0'],
            'tarif_jam_berikutnya' => ['required', 'integer', 'min:0'],
        ]);

        $tarif->update($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Tarif diperbarui: '.$tarif->jenis_kendaraan.' | Rp '.$tarif->tarif_jam_pertama.' / jam pertama, Rp '.$tarif->tarif_jam_berikutnya.' / jam berikutnya',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Tarif berhasil diperbarui.')
            ->with('active_tab', $request->input('active_tab', 'tarif'));
    }

    public function destroyTarif(Request $request, Tarif $tarif): RedirectResponse
    {
        $jenisKendaraan = $tarif->jenis_kendaraan;
        $tarifJamPertama = $tarif->tarif_jam_pertama;
        $tarifJamBerikutnya = $tarif->tarif_jam_berikutnya;

        $tarif->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Tarif dihapus: '.$jenisKendaraan.' | Rp '.$tarifJamPertama.' / jam pertama, Rp '.$tarifJamBerikutnya.' / jam berikutnya',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Tarif berhasil dihapus.')
            ->with('active_tab', $request->input('active_tab', 'tarif'));
    }

    /**
     * Menyimpan konfigurasi jenis pelanggan, termasuk status khusus seperti
     * bebas denda dan gratis parkir yang memengaruhi perhitungan akhir.
     */
    public function storeJenisPelanggan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        // Checkbox tanpa value cuma kirim "on" saat dicentang & gak kirim apa-apa saat tidak.
        // request()->boolean() otomatis handle keduanya jadi true/false, jadi gak perlu masuk rule validasi.
        $validated['is_gratis_parkir'] = $request->boolean('is_gratis_parkir');
        $validated['is_parkir_flat'] = $request->boolean('is_parkir_flat');
        $validated['is_bebas_denda'] = $request->boolean('is_bebas_denda');

        $jenisPelanggan = JenisPelanggan::create($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Jenis pelanggan ditambahkan: '.$jenisPelanggan->nama.' ('.$jenisPelanggan->status.')',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Jenis pelanggan berhasil ditambahkan.')
            ->with('active_tab', $request->input('active_tab', 'jenis'));
    }

    public function updateJenisPelanggan(Request $request, JenisPelanggan $jenisPelanggan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        $validated['is_gratis_parkir'] = $request->boolean('is_gratis_parkir');
        $validated['is_parkir_flat'] = $request->boolean('is_parkir_flat');
        $validated['is_bebas_denda'] = $request->boolean('is_bebas_denda');

        $jenisPelanggan->update($validated);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Jenis pelanggan diperbarui: '.$jenisPelanggan->nama.' ('.$jenisPelanggan->status.')',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Jenis pelanggan berhasil diperbarui.')
            ->with('active_tab', $request->input('active_tab', 'jenis'));
    }

    public function destroyJenisPelanggan(Request $request, JenisPelanggan $jenisPelanggan): RedirectResponse
    {
        $namaJenis = $jenisPelanggan->nama;
        $statusJenis = $jenisPelanggan->status;

        $jenisPelanggan->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Jenis pelanggan dihapus: '.$namaJenis.' ('.$statusJenis.')',
        ]);

        return redirect()->route('management.tarif.index')
            ->with('success', 'Jenis pelanggan berhasil dihapus.')
            ->with('active_tab', $request->input('active_tab', 'jenis'));
    }
}

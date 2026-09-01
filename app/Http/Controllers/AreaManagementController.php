<?php

/*
 * Catatan pembelajaran
 * Controller ini menangani CRUD data area parkir. Alur umum: ambil request, validasi input, hitung relasi tarif/jenis, lalu simpan atau hapus data dalam database.
 * Prinsip umum: request -> validasi -> model -> response.
 */


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
     *
     * Fungsi index adalah halaman utama manajemen area. Di sini data area, tarif,
     * dan jenis pelanggan diambil agar form tambah/edit bisa menampilkan pilihan yang
     * sesuai. Artinya admin bisa mengatur area parkir dengan relasi yang benar.
     *
     * @return View dengan data area, tarif, dan jenis pelanggan.
     */
    public function index(): View
    {
        $areas = AreaParkir::with(['tarif', 'jenisPelanggans'])->orderBy('created_at', 'desc')->get();
        $tarifs = Tarif::orderBy('jenis_kendaraan')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('nama')->get();

        return view('management.area.index', compact('areas', 'tarifs', 'jenisPelanggan'));
    }

    /**
     * Menyimpan area parkir baru.
     *
     * Input yang masuk biasanya terdiri dari:
     * - nama: nama area parkir.
     * - lokasi: lokasi fisik area parkir.
     * - kapasitas: jumlah maksimal kendaraan yang bisa masuk.
     * - tarif_id: tarif yang dipakai untuk area tersebut.
     * - jenis_pelanggan_ids: daftar jenis pelanggan yang boleh masuk ke area ini.
     *
     * Setelah data disimpan, fungsi ini juga menyinkronkan relasi area dengan jenis
     * pelanggan supaya aturan akses area tetap konsisten dengan kebutuhan bisnis.
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

    /**
     * Mengubah data area yang sudah ada.
     *
     * Fungsi update dipanggil ketika admin mengganti nama area, lokasi, kapasitas,
     * atau mengubah relasi jenis pelanggan yang boleh masuk. Tujuannya adalah agar
     * area parkir tetap sesuai dengan operasional yang sedang berlaku.
     */
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

    /**
     * Menghapus area parkir.
     *
     * Fungsi destroy dipakai saat area sudah tidak dipakai lagi atau harus dihapus
     * karena perubahan struktur parkir. Sebelum data benar-benar dihapus, sistem akan
     * mencatat log aktivitas agar jejak perubahan tetap terdokumentasi.
     */
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

<?php

/*
 * Catatan pembelajaran
 * Controller ini mengelola pengaturan aplikasi seperti batas waktu grace period, tarif denda, dan konfigurasi sistem yang dipakai saat proses parkir.
 * Prinsip umum: request -> validasi -> model -> response.
 */


namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingManagementController extends Controller
{
    /**
     * Menampilkan halaman pengaturan aplikasi.
     *
     * Fungsi index menyiapkan semua setting yang dipakai sistem parkir, seperti denda,
     * masa tenggang, dan aturan lain yang memengaruhi hitung biaya. Ini penting karena
     * banyak fitur parkir membacanya secara dinamis, bukan hardcode di controller.
     */
    public function index(): View
    {
        $settings = Setting::orderBy('key')->get();

        return view('management.setting.index', compact('settings'));
    }

    /**
     * Menyimpan satu setting baru atau memperbarui setting yang sudah ada.
     *
     * Input yang umum masuk:
     * - key: nama pengaturan, misalnya denda_karcis_hilang atau menit_grace_period.
     * - value: nilai pengaturan.
     * - description: keterangan singkat untuk admin.
     *
     * Karena pengaturan ini dibaca di banyak tempat, struktur key harus konsisten supaya
     * logic parkir tetap berjalan sesuai yang diinginkan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = Setting::updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => $validated['value'] ?? '',
                'description' => $validated['description'] ?? null,
            ],
        );

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Pengaturan disimpan: ' . $setting->key . ' = ' . $setting->value,
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Menyimpan banyak setting sekaligus dalam satu kali submit.
     *
     * Biasanya dipakai untuk form pengaturan yang banyak item, misalnya semua konfigurasi
     * parkir dalam satu halaman. Fungsi ini menyimpan semua value ke tabel settings secara
     * bersamaan agar admin tidak perlu satu per satu.
     */
    public function saveBulk(Request $request): RedirectResponse
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Mengubah pengaturan parkir: ' . json_encode(array_keys($settings)),
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    /**
     * Menghapus setting tertentu.
     *
     * Fungsi ini digunakan jika sebuah pengaturan tidak lagi dipakai atau ingin dihapus
     * karena kebijakan berubah. Log dibuat supaya perubahan pengaturan terdokumentasi.
     */
    public function destroy(Setting $setting): RedirectResponse
    {
        $keySetting = $setting->key;
        $valueSetting = $setting->value;

        $setting->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Pengaturan dihapus: ' . $keySetting . ' = ' . $valueSetting,
        ]);

        return redirect()->route('management.setting.index')->with('success', 'Pengaturan berhasil dihapus.');
    }
}

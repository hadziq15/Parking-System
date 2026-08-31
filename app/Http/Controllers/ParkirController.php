<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\JenisPelanggan;
use App\Models\Kendaraan;
use App\Models\Log;
use App\Models\Setting;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParkirController extends Controller
{
    public function masuk(): View
    {
        $areas = AreaParkir::with('tarif')->orderBy('nama')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('nama')->get();
        $registeredVehicles = Kendaraan::with('jenisPelanggan')->get()->map(function ($kendaraan) {
            return [
                'plat_nomor' => strtoupper(trim((string) $kendaraan->plat_nomor)),
                'jenis_pelanggan' => $kendaraan->jenisPelanggan?->nama ?? 'Reguler',
            ];
        });

        return view('parkir.masuk', compact('areas', 'jenisPelanggan', 'registeredVehicles'));
    }

    public function keluar(): View
    {
        $activeTransactions = Transaksi::with(['kendaraan', 'jenisPelanggan', 'areaParkir', 'tarif'])
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar')
            ->orderBy('waktu_masuk', 'desc')
            ->get();

        return view('parkir.keluar', compact('activeTransactions'));
    }

    public function storeMasuk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plat_nomor' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'jenis_kendaraan' => ['required', 'in:mobil,motor,truk'],
            'area_parkir_id' => ['required', 'uuid', 'exists:area_parkirs,id'],
        ]);

        $regularType = JenisPelanggan::query()
            ->whereRaw('LOWER(nama) = ?', ['reguler'])
            ->first();

        if (! $regularType) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Data jenis pelanggan reguler tidak tersedia mohon tambahkan terlebih dahulu di jenis pelanggan',
            ]);
        }

        $platNomor = strtoupper(trim((string) $validated['plat_nomor']));
        $registeredVehicle = Kendaraan::query()->whereRaw('UPPER(plat_nomor) = ?', [$platNomor])->first();
        $jenisPelanggan = $registeredVehicle?->jenisPelanggan ?? $regularType;

        $area = AreaParkir::with('tarif')->findOrFail($validated['area_parkir_id']);

        if (! $area->tarif) {
            return back()->withInput()->withErrors([
                'area_parkir_id' => 'Area parkir ini belum memiliki tarif. Silakan atur tarif area terlebih dahulu.',
            ]);
        }

        $existingActiveTransaction = Transaksi::where('plat_nomor', $platNomor)
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar')
            ->exists();

        if ($existingActiveTransaction) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Kendaraan ini sudah masuk dan masih aktif di parkir.',
            ]);
        }

        $transaction = Transaksi::create([
            'kendaraan_id' => $registeredVehicle?->id,
            'plat_nomor' => $platNomor,
            'jenis_pelanggan_id' => $jenisPelanggan->id,
            'tarif_id' => $area->tarif->id,
            'area_parkir_id' => $area->id,
            'user_id' => Auth::id(),
            'waktu_masuk' => now(),
            'status' => 'masuk',
        ]);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan masuk: ' . $platNomor . ' (' . $jenisPelanggan->nama . ')',
        ]);

        return redirect()->route('parkir.masuk')->with('success', 'Kendaraan berhasil masuk ke area parkir.');
    }

    public function storeKeluar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plat_nomor' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\s]+$/'],
        ]);

        $platNomor = strtoupper(trim((string) $validated['plat_nomor']));

        $transaction = Transaksi::with(['jenisPelanggan', 'tarif'])
            ->where('plat_nomor', $platNomor)
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar')
            ->latest('waktu_masuk')
            ->first();

        if (! $transaction) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Kendaraan tidak ditemukan dalam parkir aktif.',
            ]);
        }

        $waktuMasuk = $transaction->waktu_masuk;
        $waktuKeluar = now();
        $durasiMenit = max(0, $waktuMasuk->diffInMinutes($waktuKeluar));
        $gracePeriod = (int) Setting::valueOf('menit_grace_period', 0);
        $halfPriceMinutes = (int) Setting::valueOf('menit_tarif_setengah', 0);
        $jenisPelanggan = $transaction->jenisPelanggan;
        $tarif = $transaction->tarif;

        if (! $tarif) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Tarif untuk kendaraan ini belum tersedia.',
            ]);
        }

        $totalBayar = $this->calculateParkingTotal($durasiMenit, $gracePeriod, $halfPriceMinutes, $jenisPelanggan, $tarif);

        $transaction->update([
            'waktu_keluar' => $waktuKeluar,
            'durasi' => $durasiMenit,
            'total_bayar' => $totalBayar,
            'status' => 'keluar',
        ]);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan keluar: ' . $platNomor . ' | Durasi ' . $durasiMenit . ' menit | Total Rp ' . number_format($totalBayar, 0, ',', '.'),
        ]);

        return redirect()->route('parkir.keluar')->with('success', 'Kendaraan keluar berhasil. Total pembayaran: Rp ' . number_format($totalBayar, 0, ',', '.'));
    }

    protected function calculateParkingTotal(int $durasiMenit, int $gracePeriod, int $halfPriceMinutes, ?JenisPelanggan $jenisPelanggan, $tarif): int
    {
        if ($jenisPelanggan && $jenisPelanggan->is_gratis_parkir) {
            return 0;
        }

        if ($durasiMenit <= $gracePeriod) {
            return 0;
        }

        $jamPertama = (int) ($tarif->tarif_jam_pertama ?? 0);
        $jamBerikutnya = (int) ($tarif->tarif_jam_berikutnya ?? 0);

        if ($halfPriceMinutes > 0 && $halfPriceMinutes > $gracePeriod && $durasiMenit <= ($gracePeriod + $halfPriceMinutes)) {
            return (int) round($jamPertama / 2);
        }

        if ($jenisPelanggan && $jenisPelanggan->is_parkir_flat) {
            return $jamPertama;
        }

        $durasiSetelahGrace = max(0, $durasiMenit - $gracePeriod);
        $durasiJamPertama = min($durasiSetelahGrace, 60);
        $total = $jamPertama;

        if ($durasiSetelahGrace > 60) {
            $total += (int) ceil(($durasiSetelahGrace - 60) / 60) * $jamBerikutnya;
        }

        if ($durasiSetelahGrace <= 0) {
            return 0;
        }

        return max(0, $total);
    }
}

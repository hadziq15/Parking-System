<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\JenisPelanggan;
use App\Models\Kendaraan;
use App\Models\Log;
use App\Models\Setting;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParkirController extends Controller
{
    /**
     * Menyiapkan halaman kendaraan masuk: daftar area parkir, jenis pelanggan,
     * serta kendaraan yang sudah terdaftar agar operator cepat mengisi form.
     */
    public function masuk(): View
    {
        $areas = AreaParkir::with('tarif')->orderBy('nama')->get();
        $jenisPelanggan = JenisPelanggan::orderBy('nama')->get();
        $registeredVehicles = Kendaraan::with('jenisPelanggan')->get()->map(function ($kendaraan) {
            return [
                'plat_nomor' => strtoupper(trim((string) $kendaraan->plat_nomor)),
                'jenis_kendaraan' => $kendaraan->jenis_kendaraan,
                'jenis_pelanggan' => $kendaraan->jenisPelanggan?->nama ?? 'Reguler',
            ];
        });

        return view('parkir.masuk', compact('areas', 'jenisPelanggan', 'registeredVehicles'));
    }

    public function keluar(): View
    {
        return view('parkir.keluar');
    }

    public function terparkir(): View
    {
        $activeTransactions = Transaksi::with(['kendaraan', 'jenisPelanggan', 'areaParkir', 'tarif'])
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar')
            ->orderBy('waktu_masuk', 'desc')
            ->get();

        return view('parkir.terparkir', compact('activeTransactions'));
    }

    /**
     * Proses kendaraan masuk: validasi plat, cek area tarif, serta catat log
     * aktivitas untuk menjaga riwayat parkir tetap jelas.
     */
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

        if ($registeredVehicle && strtolower((string) $registeredVehicle->jenis_kendaraan) !== strtolower((string) $validated['jenis_kendaraan'])) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Nomor polisi ini terdaftar dengan jenis kendaraan '.strtolower((string) $registeredVehicle->jenis_kendaraan).'. Silakan sesuaikan jenis kendaraan.',
            ]);
        }

        $area = AreaParkir::with('tarif')->findOrFail($validated['area_parkir_id']);

        if (! $area->tarif) {
            return back()->withInput()->withErrors([
                'area_parkir_id' => 'Area parkir ini belum memiliki tarif. Silakan atur tarif area terlebih dahulu.',
            ]);
        }

        if ($area->tarif->jenis_kendaraan !== strtolower((string) $validated['jenis_kendaraan'])) {
            return back()->withInput()->withErrors([
                'area_parkir_id' => 'Area parkir yang dipilih tidak sesuai dengan jenis kendaraan '.strtolower((string) $validated['jenis_kendaraan']).'.',
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

        $nomorKarcis = $this->generateTicketNumber();

        $transaction = Transaksi::create([
            'kendaraan_id' => $registeredVehicle?->id,
            'plat_nomor' => $platNomor,
            'nomor_karcis' => $nomorKarcis,
            'jenis_kendaraan' => $validated['jenis_kendaraan'],
            'jenis_pelanggan_id' => $jenisPelanggan->id,
            'tarif_id' => $area->tarif->id,
            'area_parkir_id' => $area->id,
            'user_id' => Auth::id(),
            'waktu_masuk' => now(),
            'status' => 'masuk',
        ]);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan masuk: '.$platNomor.' ('.$jenisPelanggan->nama.') | Nomor karcis: '.$transaction->nomor_karcis,
        ]);

        return redirect()->route('parkir.masuk')
            ->with('success', 'Kendaraan berhasil masuk ke area parkir. Nomor karcis: '.$transaction->nomor_karcis)
            ->with('ticket_url', route('parkir.ticket.download', $transaction));
    }

    /**
     * Proses kendaraan keluar: menghitung durasi parkir, menerapkan tarif,
     * dan menghasilkan total akhir yang dibayarkan oleh pelanggan.
     */
    public function storeKeluar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plat_nomor' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'nomor_karcis' => ['nullable', 'string', 'max:50'],
            'karcis_hilang' => ['nullable', 'boolean'],
        ]);

        $identifier = trim((string) ($validated['plat_nomor'] ?? ''));
        $nomorKarcis = trim((string) ($validated['nomor_karcis'] ?? ''));

        if ($identifier === '' && $nomorKarcis === '') {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Masukkan nomor polisi atau nomor karcis untuk memproses kendaraan keluar.',
            ]);
        }

        if ($nomorKarcis === '' && str_contains(strtoupper($identifier), 'KRC-')) {
            $nomorKarcis = strtoupper($identifier);
            $identifier = '';
        }

        $query = Transaksi::query()
            ->with(['jenisPelanggan', 'tarif'])
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar');

        if ($nomorKarcis !== '') {
            $query->whereRaw('UPPER(nomor_karcis) = ?', [strtoupper($nomorKarcis)]);
        } else {
            $query->whereRaw('UPPER(plat_nomor) = ?', [strtoupper($identifier)]);
        }

        $transaction = $query->latest('waktu_masuk')->first();

        if (! $transaction) {
            return back()->withInput()->withErrors([
                'plat_nomor' => 'Data kendaraan tidak ditemukan dalam parkir aktif.',
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

        $karcisHilang = (bool) $request->boolean('karcis_hilang');
        $dendaKarcisHilang = 0;

        if ($karcisHilang) {
            if (! $jenisPelanggan || ! $jenisPelanggan->is_bebas_denda) {
                $dendaKarcisHilang = (int) Setting::valueOf('denda_karcis_hilang', 0);
            }
        }

        $totalBayar = $this->calculateParkingTotal($durasiMenit, $gracePeriod, $halfPriceMinutes, $jenisPelanggan, $tarif) + $dendaKarcisHilang;

        $transaction->update([
            'waktu_keluar' => $waktuKeluar,
            'durasi' => $durasiMenit,
            'denda' => $dendaKarcisHilang,
            'total_bayar' => $totalBayar,
            'status' => 'keluar',
        ]);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'Kendaraan keluar: '.$transaction->plat_nomor.' | Durasi '.$durasiMenit.' menit | Denda karcis hilang Rp '.number_format($dendaKarcisHilang, 0, ',', '.').' | Total Rp '.number_format($totalBayar, 0, ',', '.'),
        ]);

        $message = 'Kendaraan keluar berhasil. Total pembayaran: Rp '.number_format($totalBayar, 0, ',', '.');

        if ($karcisHilang) {
            $message .= ' (Karcis hilang: Rp '.number_format($dendaKarcisHilang, 0, ',', '.').')';
        }

        return redirect()->route('parkir.keluar')
            ->with('success', $message)
            ->with('exit_ticket_url', route('parkir.ticket.exit.download', $transaction));
    }

    public function previewTicket(Transaksi $transaction)
    {
        $transaction->load(['kendaraan', 'areaParkir', 'jenisPelanggan', 'tarif']);

        $pdf = Pdf::loadView('tickets.entry', $this->buildTicketEntryData($transaction))
            ->setPaper([0, 0, 220, 420], 'portrait');

        return $pdf->stream('karcis-masuk-'.$transaction->nomor_karcis.'.pdf', ['Attachment' => false]);
    }

    public function previewExitTicket(Transaksi $transaction)
    {
        $transaction->load(['kendaraan', 'areaParkir', 'jenisPelanggan', 'tarif']);

        $pdf = Pdf::loadView('tickets.exit', $this->buildTicketExitData($transaction))
            ->setPaper([0, 0, 220, 420], 'portrait');

        return $pdf->stream('karcis-keluar-'.$transaction->nomor_karcis.'.pdf', ['Attachment' => false]);
    }

    protected function buildTicketEntryData(Transaksi $transaction): array
    {
        return [
            'nomor_karcis' => $transaction->nomor_karcis ?? '-',
            'plat_nomor' => $transaction->plat_nomor ?? '-',
            'jenis_kendaraan' => $transaction->jenis_kendaraan ?? $transaction->kendaraan?->jenis_kendaraan ?? 'Tidak diketahui',
            'area_nama' => $transaction->areaParkir?->nama ?? '-',
            'waktu_masuk' => $transaction->waktu_masuk?->format('d M Y H:i') ?? '-',
        ];
    }

    protected function buildTicketExitData(Transaksi $transaction): array
    {
        return [
            'nomor_karcis' => $transaction->nomor_karcis ?? '-',
            'plat_nomor' => $transaction->plat_nomor ?? '-',
            'jenis_kendaraan' => $transaction->jenis_kendaraan ?? $transaction->kendaraan?->jenis_kendaraan ?? 'Tidak diketahui',
            'area_nama' => $transaction->areaParkir?->nama ?? '-',
            'waktu_keluar' => $transaction->waktu_keluar?->format('d M Y H:i') ?? '-',
            'total_bayar' => (int) ($transaction->total_bayar ?? 0),
        ];
    }

    protected function generateTicketNumber(): string
    {
        do {
            $nomorKarcis = 'KRC-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (Transaksi::where('nomor_karcis', $nomorKarcis)->exists());

        return $nomorKarcis;
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

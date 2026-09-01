<?php

/*
 * Catatan pembelajaran
 * Controller ini bertanggung jawab membuat laporan transaksi parkir dan mengekspor data dalam bentuk PDF agar bisa dicetak atau dibagikan.
 * Prinsip umum: request -> validasi -> model -> response.
 */


namespace App\Http\Controllers;

use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman laporan transaksi parkir.
     *
     * Fungsi index membaca filter tanggal dari request, mengambil data transaksi sesuai
     * rentang waktu, lalu mengirim ke view laporan. Di halaman ini user bisa melihat
     * jumlah transaksi, pendapatan, dan status parkir dalam periode tertentu.
     */
    public function index(Request $request): View
    {
        [$startDate, $endDate, $transactions, $totals] = $this->resolveReportData($request);

        return view('reports.transaksi', compact('transactions', 'startDate', 'endDate', 'totals'));
    }

    /**
     * Mengekspor laporan transaksi ke file PDF.
     *
     * Fungsi ini menggunakan DomPDF untuk membuat dokumen PDF dari data transaksi yang
     * sudah difilter. Tujuan utamanya adalah cetak laporan dan dibagikan dalam format
     * yang siap disimpan atau dicetak.
     */
    public function exportPdf(Request $request)
    {
        [$startDate, $endDate, $transactions, $totals] = $this->resolveReportData($request);

        $pdf = Pdf::loadView('reports.transaksi-pdf', compact('transactions', 'startDate', 'endDate', 'totals'))
            ->setPaper('A4', 'landscape');

        return $pdf->download(sprintf('laporan-transaksi-%s-sampai-%s.pdf', $startDate, $endDate));
    }

    /**
     * Mengambil data laporan berdasarkan rentang tanggal.
     *
     * Fungsi ini memeriksa parameter start_date dan end_date, lalu menyusun query transaksi
     * sesuai kebutuhan. Hasilnya berisi data transaksi yang akan ditampilkan di halaman
     * laporan dan juga total ringkas (jumlah, pendapatan, masuk, keluar).
     */
    protected function resolveReportData(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $query = Transaksi::query()
            ->with(['kendaraan', 'jenisPelanggan', 'tarif', 'areaParkir', 'user'])
            ->when(Auth::user()?->role && ! in_array(Auth::user()->role, ['admin', 'super_admin', 'owner'], true), function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('waktu_masuk', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('waktu_masuk', '<=', $endDate);
            })
            ->orderBy('waktu_masuk', 'desc');

        $transactions = $query->get();

        $totals = [
            'count' => $transactions->count(),
            'income' => (int) $transactions->sum('total_bayar'),
            'active' => $transactions->where('status', 'masuk')->count(),
            'closed' => $transactions->where('status', 'keluar')->count(),
        ];

        return [$startDate, $endDate, $transactions, $totals];
    }
}

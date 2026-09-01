<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$startDate, $endDate, $transactions, $totals] = $this->resolveReportData($request);

        return view('reports.transaksi', compact('transactions', 'startDate', 'endDate', 'totals'));
    }

    public function exportPdf(Request $request)
    {
        [$startDate, $endDate, $transactions, $totals] = $this->resolveReportData($request);

        $pdf = Pdf::loadView('reports.transaksi-pdf', compact('transactions', 'startDate', 'endDate', 'totals'))
            ->setPaper('A4', 'landscape');

        return $pdf->download(sprintf('laporan-transaksi-%s-sampai-%s.pdf', $startDate, $endDate));
    }

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

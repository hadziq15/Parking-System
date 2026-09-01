<?php

use App\Http\Controllers\AreaManagementController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ParkirController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingManagementController;
use App\Http\Controllers\TarifManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VehicleManagementController;
use App\Models\AreaParkir;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Route;

// Route utama: pengguna yang sudah login diarahkan ke dashboard,
// sementara tamu langsung dibawa ke halaman login agar tidak
// menampilkan halaman welcome yang tidak dipakai lagi.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// route user (pegawai)
Route::middleware(['auth', 'verified', 'role:user,admin,super_admin,owner'])->group(function () {
    Route::get('/dashboard', function () {
        $today = now()->toDateString();

        $transactionsToday = Transaksi::query()
            ->whereDate('waktu_masuk', $today)
            ->count();

        $currentlyParked = Transaksi::query()
            ->where('status', 'masuk')
            ->whereNull('waktu_keluar')
            ->count();

        $incomeToday = Transaksi::query()
            ->where('status', 'keluar')
            ->whereDate('waktu_keluar', $today)
            ->sum('total_bayar');

        $areas = AreaParkir::query()
            ->with('tarif')
            ->get()
            ->map(function ($area) {
                $occupied = Transaksi::query()
                    ->where('area_parkir_id', $area->id)
                    ->where('status', 'masuk')
                    ->whereNull('waktu_keluar')
                    ->count();

                $capacity = max(1, (int) $area->kapasitas);
                $percent = min(100, (int) round(($occupied / $capacity) * 100));

                return [
                    'id' => $area->id,
                    'nama' => $area->nama,
                    'lokasi' => $area->lokasi,
                    'kapasitas' => $capacity,
                    'terisi' => $occupied,
                    'tersisa' => max(0, $capacity - $occupied),
                    'persentase' => $percent,
                ];
            });

        $recentTransactions = Transaksi::query()
            ->with(['areaParkir', 'jenisPelanggan'])
            ->latest('waktu_masuk')
            ->limit(10)
            ->get();

        return view('dashboard', compact('transactionsToday', 'currentlyParked', 'incomeToday', 'areas', 'recentTransactions'));
    })->name('dashboard');

    Route::get('/kendaraan-masuk', [ParkirController::class, 'masuk'])->name('parkir.masuk');
    Route::post('/kendaraan-masuk', [ParkirController::class, 'storeMasuk'])->name('parkir.masuk.store');

    Route::get('/kendaraan-keluar', [ParkirController::class, 'keluar'])->name('parkir.keluar');
    Route::post('/kendaraan-keluar', [ParkirController::class, 'storeKeluar'])->name('parkir.keluar.store');
    Route::get('/kendaraan-terparkir', [ParkirController::class, 'terparkir'])->name('parkir.terparkir');

    Route::get('/log-aktivitas', [LogController::class, 'index'])->name('logs.index');
    Route::get('/parkir/tiket/{transaksi}', [ParkirController::class, 'previewTicket'])->name('parkir.ticket.download');
    Route::get('/parkir/tiket-keluar/{transaksi}', [ParkirController::class, 'previewExitTicket'])->name('parkir.ticket.exit.download');
});

// route admin
Route::middleware(['auth', 'verified', 'role:admin,super_admin,owner'])->group(function () {
    Route::get('/log-aktivitas/admin', [LogController::class, 'adminIndex'])->name('logs.admin.index');
    // route user management
    Route::prefix('user-management')->name('user-management.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('management')->name('management.')->group(function () {
        Route::get('/tarif', [TarifManagementController::class, 'index'])->name('tarif.index');
        Route::post('/tarif', [TarifManagementController::class, 'storeTarif'])->name('tarif.store');
        Route::put('/tarif/{tarif}', [TarifManagementController::class, 'updateTarif'])->name('tarif.update');
        Route::delete('/tarif/{tarif}', [TarifManagementController::class, 'destroyTarif'])->name('tarif.destroy');

        Route::post('/jenis-pelanggan', [TarifManagementController::class, 'storeJenisPelanggan'])->name('jenis-pelanggan.store');
        Route::put('/jenis-pelanggan/{jenisPelanggan}', [TarifManagementController::class, 'updateJenisPelanggan'])->name('jenis-pelanggan.update');
        Route::delete('/jenis-pelanggan/{jenisPelanggan}', [TarifManagementController::class, 'destroyJenisPelanggan'])->name('jenis-pelanggan.destroy');

        Route::get('/area', [AreaManagementController::class, 'index'])->name('area.index');
        Route::post('/area', [AreaManagementController::class, 'store'])->name('area.store');
        Route::put('/area/{areaParkir}', [AreaManagementController::class, 'update'])->name('area.update');
        Route::delete('/area/{areaParkir}', [AreaManagementController::class, 'destroy'])->name('area.destroy');

        Route::get('/kendaraan', [VehicleManagementController::class, 'index'])->name('vehicle.index');
        Route::post('/kendaraan', [VehicleManagementController::class, 'store'])->name('vehicle.store');
        Route::put('/kendaraan/{kendaraan}', [VehicleManagementController::class, 'update'])->name('vehicle.update');
        Route::delete('/kendaraan/{kendaraan}', [VehicleManagementController::class, 'destroy'])->name('vehicle.destroy');

        Route::get('/setting', [SettingManagementController::class, 'index'])->name('setting.index');
        Route::post('/setting', [SettingManagementController::class, 'store'])->name('setting.store');
        Route::post('/setting/bulk', [SettingManagementController::class, 'saveBulk'])->name('setting.bulk');
        Route::delete('/setting/{setting}', [SettingManagementController::class, 'destroy'])->name('setting.destroy');
    });

    Route::get('/laporan-transaksi', [ReportController::class, 'index'])->name('report.transaksi.index');
    Route::get('/laporan-transaksi/export-pdf', [ReportController::class, 'exportPdf'])->name('report.transaksi.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

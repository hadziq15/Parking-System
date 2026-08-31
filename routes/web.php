<?php

use App\Http\Controllers\AreaManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingManagementController;
use App\Http\Controllers\TarifManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VehicleManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// route user (pegawai)
Route::middleware(['auth', 'verified', 'role:user,admin,super_admin,owner'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/kendaraan-masuk', [\App\Http\Controllers\ParkirController::class, 'masuk'])->name('parkir.masuk');
    Route::post('/kendaraan-masuk', [\App\Http\Controllers\ParkirController::class, 'storeMasuk'])->name('parkir.masuk.store');

    Route::get('/kendaraan-keluar', [\App\Http\Controllers\ParkirController::class, 'keluar'])->name('parkir.keluar');
    Route::post('/kendaraan-keluar', [\App\Http\Controllers\ParkirController::class, 'storeKeluar'])->name('parkir.keluar.store');

    Route::get('/log-aktivitas', [\App\Http\Controllers\LogController::class, 'index'])->name('logs.index');
});

// route admin
Route::middleware(['auth', 'verified', 'role:admin,super_admin'])->group(function () {
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
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

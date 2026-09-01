<?php

use App\Models\Tarif;
use App\Models\Transaksi;
use App\Models\User;

test('owner can view transaction report filtered by period', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'motor',
        'tarif_jam_pertama' => 2000,
        'tarif_jam_berikutnya' => 1000,
    ]);

    Transaksi::create([
        'plat_nomor' => 'B 4444 ABC',
        'nomor_karcis' => 'KRC-240901-TEST1',
        'tarif_id' => $tarif->id,
        'user_id' => $owner->id,
        'waktu_masuk' => now()->subDays(2),
        'waktu_keluar' => now()->subDays(1),
        'durasi' => 300,
        'total_bayar' => 5000,
        'status' => 'keluar',
    ]);

    $response = $this->actingAs($owner)->get(route('report.transaksi.index', [
        'start_date' => now()->subDays(7)->toDateString(),
        'end_date' => now()->toDateString(),
    ]));

    $response->assertOk();
    $response->assertSee('Laporan Transaksi');
    $response->assertSee('B 4444 ABC');
});

test('owner can export transaction report as pdf', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 5000,
        'tarif_jam_berikutnya' => 3000,
    ]);

    Transaksi::create([
        'plat_nomor' => 'B 3333 CDE',
        'nomor_karcis' => 'KRC-240901-TEST2',
        'tarif_id' => $tarif->id,
        'user_id' => $owner->id,
        'waktu_masuk' => now()->subDay(),
        'waktu_keluar' => now(),
        'durasi' => 120,
        'total_bayar' => 8000,
        'status' => 'keluar',
    ]);

    $response = $this->actingAs($owner)->get(route('report.transaksi.export', [
        'start_date' => now()->subDays(7)->toDateString(),
        'end_date' => now()->toDateString(),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

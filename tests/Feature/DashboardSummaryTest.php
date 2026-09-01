<?php

use App\Models\AreaParkir;
use App\Models\Tarif;
use App\Models\Transaksi;
use App\Models\User;

test('dashboard shows daily summary, area capacity, and recent transactions', function () {
    $user = User::factory()->create(['role' => 'user']);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 5000,
        'tarif_jam_berikutnya' => 3000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area A',
        'lokasi' => 'Lantai 1',
        'kapasitas' => 5,
        'tarif_id' => $tarif->id,
    ]);

    Transaksi::create([
        'plat_nomor' => 'B 1111 AAA',
        'nomor_karcis' => 'KRC-240901-AAA111',
        'tarif_id' => $tarif->id,
        'area_parkir_id' => $area->id,
        'user_id' => $user->id,
        'waktu_masuk' => now(),
        'status' => 'masuk',
    ]);

    Transaksi::create([
        'plat_nomor' => 'B 2222 BBB',
        'nomor_karcis' => 'KRC-240901-BBB222',
        'tarif_id' => $tarif->id,
        'area_parkir_id' => $area->id,
        'user_id' => $user->id,
        'waktu_masuk' => now()->subHour(),
        'waktu_keluar' => now(),
        'durasi' => 60,
        'total_bayar' => 5000,
        'status' => 'keluar',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Transaksi Hari Ini');
    $response->assertSee('Sedang Terparkir');
    $response->assertSee('Pendapatan Hari Ini');
    $response->assertSee('Status Area Parkir');
    $response->assertSee('Transaksi Terbaru');
    $response->assertSee('Area A');
    $response->assertSee('B 1111 AAA');
});

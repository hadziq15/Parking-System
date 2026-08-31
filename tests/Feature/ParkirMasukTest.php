<?php

use App\Models\AreaParkir;
use App\Models\JenisPelanggan;
use App\Models\Kendaraan;
use App\Models\Setting;
use App\Models\Tarif;
use App\Models\Transaksi;
use App\Models\User;

test('transaction requires regular customer type before parking entry', function () {
    $user = User::factory()->create(['role' => 'user']);
    $tarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 5000,
        'tarif_jam_berikutnya' => 3000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area A',
        'lokasi' => 'Lantai 1',
        'kapasitas' => 20,
        'tarif_id' => $tarif->id,
    ]);

    $response = $this->actingAs($user)->post(route('parkir.masuk.store'), [
        'plat_nomor' => 'B 1234 ABC',
        'jenis_kendaraan' => 'mobil',
        'area_parkir_id' => $area->id,
    ]);

    $response->assertSessionHasErrors(['plat_nomor']);
    $this->assertDatabaseMissing('transaksis', ['plat_nomor' => 'B 1234 ABC']);
    $this->assertDatabaseMissing('kendaraans', ['plat_nomor' => 'B 1234 ABC']);
});

test('unregistered vehicle is stored only in transaction as regular type', function () {
    $user = User::factory()->create(['role' => 'user']);
    $jenisReguler = JenisPelanggan::create([
        'nama' => 'Reguler',
        'deskripsi' => 'Pelanggan biasa',
        'status' => 'aktif',
    ]);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 5000,
        'tarif_jam_berikutnya' => 3000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area A',
        'lokasi' => 'Lantai 1',
        'kapasitas' => 20,
        'tarif_id' => $tarif->id,
    ]);

    $response = $this->actingAs($user)->post(route('parkir.masuk.store'), [
        'plat_nomor' => 'B 1234 ABC',
        'jenis_kendaraan' => 'mobil',
        'area_parkir_id' => $area->id,
    ]);

    $response->assertRedirect(route('parkir.masuk'));
    $this->assertDatabaseMissing('kendaraans', ['plat_nomor' => 'B 1234 ABC']);
    $this->assertDatabaseHas('transaksis', [
        'plat_nomor' => 'B 1234 ABC',
        'jenis_pelanggan_id' => $jenisReguler->id,
        'status' => 'masuk',
    ]);
});

test('vehicle exit applies grace and half pricing rules', function () {
    $user = User::factory()->create(['role' => 'user']);
    Setting::updateOrCreate(['key' => 'menit_grace_period'], ['value' => '30']);
    Setting::updateOrCreate(['key' => 'menit_tarif_setengah'], ['value' => '60']);

    $jenisReguler = JenisPelanggan::create([
        'nama' => 'Reguler',
        'status' => 'aktif',
    ]);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 10000,
        'tarif_jam_berikutnya' => 5000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area A',
        'lokasi' => 'Lantai 1',
        'kapasitas' => 20,
        'tarif_id' => $tarif->id,
    ]);

    $transaction = Transaksi::create([
        'plat_nomor' => 'B 1234 XYZ',
        'jenis_pelanggan_id' => $jenisReguler->id,
        'tarif_id' => $tarif->id,
        'area_parkir_id' => $area->id,
        'user_id' => $user->id,
        'waktu_masuk' => now()->subMinutes(45),
        'status' => 'masuk',
    ]);

    $response = $this->actingAs($user)->post(route('parkir.keluar.store'), [
        'plat_nomor' => 'B 1234 XYZ',
    ]);

    $response->assertRedirect(route('parkir.keluar'));
    $transaction->refresh();
    expect($transaction->total_bayar)->toBe(5000)
        ->and($transaction->status)->toBe('keluar');
});

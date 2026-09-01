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

test('ticket can be generated as pdf for entry and exit', function () {
    $user = User::factory()->create(['role' => 'user']);

    $jenisReguler = JenisPelanggan::create([
        'nama' => 'Reguler',
        'status' => 'aktif',
    ]);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'motor',
        'tarif_jam_pertama' => 2000,
        'tarif_jam_berikutnya' => 1000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area Motor',
        'lokasi' => 'Lantai 2',
        'kapasitas' => 30,
        'tarif_id' => $tarif->id,
    ]);

    $transaction = Transaksi::create([
        'plat_nomor' => 'B 9090 QQQ',
        'nomor_karcis' => 'KRC-240901-TESTPDF',
        'jenis_pelanggan_id' => $jenisReguler->id,
        'tarif_id' => $tarif->id,
        'area_parkir_id' => $area->id,
        'user_id' => $user->id,
        'waktu_masuk' => now(),
        'status' => 'masuk',
    ]);

    $entryResponse = $this->actingAs($user)->get(route('parkir.ticket.download', $transaction));
    $entryResponse->assertOk();
    $entryResponse->assertHeader('Content-Type', 'application/pdf');

    $transaction->update([
        'waktu_keluar' => now(),
        'durasi' => 120,
        'total_bayar' => 5000,
        'status' => 'keluar',
    ]);

    $exitResponse = $this->actingAs($user)->get(route('parkir.ticket.exit.download', $transaction));
    $exitResponse->assertOk();
    $exitResponse->assertHeader('Content-Type', 'application/pdf');
});

test('registered vehicle type and area options are synced by vehicle type', function () {
    $user = User::factory()->create(['role' => 'user']);

    $motorTarif = Tarif::create([
        'jenis_kendaraan' => 'motor',
        'tarif_jam_pertama' => 2000,
        'tarif_jam_berikutnya' => 1000,
    ]);

    $mobilTarif = Tarif::create([
        'jenis_kendaraan' => 'mobil',
        'tarif_jam_pertama' => 5000,
        'tarif_jam_berikutnya' => 3000,
    ]);

    Kendaraan::create([
        'pemilik' => 'Budi',
        'plat_nomor' => 'B 7777 XYZ',
        'jenis_kendaraan' => 'motor',
        'warna' => 'Hitam',
    ]);

    AreaParkir::create([
        'nama' => 'Area Motor',
        'lokasi' => 'Lantai 2',
        'kapasitas' => 20,
        'tarif_id' => $motorTarif->id,
    ]);

    AreaParkir::create([
        'nama' => 'Area Mobil',
        'lokasi' => 'Lantai 1',
        'kapasitas' => 20,
        'tarif_id' => $mobilTarif->id,
    ]);

    $response = $this->actingAs($user)->get(route('parkir.masuk'));

    $response->assertOk();
    $response->assertSee('"jenis_kendaraan":"motor"', false);
    $response->assertSee('data-jenis="motor"', false);
    $response->assertSee('data-jenis="mobil"', false);
    $response->assertSee('filterAreaByVehicleType', false);
});

test('parking exit accepts ticket number and applies lost ticket fee for non exempt type', function () {
    $user = User::factory()->create(['role' => 'user']);
    Setting::updateOrCreate(['key' => 'denda_karcis_hilang'], ['value' => '25000']);

    $jenisReguler = JenisPelanggan::create([
        'nama' => 'Reguler',
        'status' => 'aktif',
    ]);

    $tarif = Tarif::create([
        'jenis_kendaraan' => 'motor',
        'tarif_jam_pertama' => 2000,
        'tarif_jam_berikutnya' => 1000,
    ]);

    $area = AreaParkir::create([
        'nama' => 'Area Motor',
        'lokasi' => 'Lantai 2',
        'kapasitas' => 20,
        'tarif_id' => $tarif->id,
    ]);

    $transaction = Transaksi::create([
        'plat_nomor' => 'B 6666 XYZ',
        'nomor_karcis' => 'KRC-240901-ABC123',
        'jenis_pelanggan_id' => $jenisReguler->id,
        'tarif_id' => $tarif->id,
        'area_parkir_id' => $area->id,
        'user_id' => $user->id,
        'waktu_masuk' => now()->subMinutes(75),
        'status' => 'masuk',
    ]);

    $response = $this->actingAs($user)->post(route('parkir.keluar.store'), [
        'plat_nomor' => 'KRC-240901-ABC123',
        'karcis_hilang' => '1',
    ]);

    $response->assertRedirect(route('parkir.keluar'));
    $transaction->refresh();
    expect($transaction->denda)->toBe(25000)
        ->and($transaction->status)->toBe('keluar');
});

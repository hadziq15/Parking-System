<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kendaraan_id', 'tarif_id', 'area_parkir_id', 'user_id', 'waktu_masuk', 'waktu_keluar', 'durasi', 'denda', 'total_bayar', 'status'])]
class Transaksi extends Model
{
    use HasUuids;

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class);
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class);
    }

    public function areaParkir(): BelongsTo
    {
        return $this->belongsTo(AreaParkir::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'waktu_keluar' => 'datetime',
            'durasi' => 'integer',
            'denda' => 'integer',
            'total_bayar' => 'integer',
        ];
    }
}

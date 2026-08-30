<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama', 'lokasi', 'kapasitas', 'tarif_id'])]
class AreaParkir extends Model
{
    use HasUuids, SoftDeletes;

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    protected function casts(): array
    {
        return ['kapasitas' => 'integer'];
    }
}

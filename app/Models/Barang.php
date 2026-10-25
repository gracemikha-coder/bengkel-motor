<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['is_active'])]
class Barang extends Model
{
    protected $table = 'barang';

    public $timestamps = false;

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

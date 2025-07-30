<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpknl extends Model
{
    /** @use HasFactory<\Database\Factories\KpknlFactory> */
    use HasFactory;

    protected $fillable = [
        "nama",
        "kode_satker",
        "alamat",
        "kota",
        "provinsi",
        "nomor_telepon"
    ];
}

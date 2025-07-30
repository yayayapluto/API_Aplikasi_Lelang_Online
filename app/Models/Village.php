<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    /** @use HasFactory<\Database\Factories\VillageFactory> */
    use HasFactory;

    protected $fillable = [
        "nama",
        "fullCode",
        "code",
        "kode_pos"
    ];

    // contoh
    //  {
    //    "nama": "ALUE BAKONG",
    //    "kode_pos": "23652",
    //    "fullCode": "1105062002",
    //    "code": null
    //  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    /** @use HasFactory<\Database\Factories\CityFactory> */
    use HasFactory;

    protected $fillable = [
        "nama",
        "fullCode",
        "code"
    ];

    // contoh
    //  {
    //    "nama": "KAB. ACEH BARAT",
    //    "fullCode": "1105",
    //    "code": null
    //  }
}

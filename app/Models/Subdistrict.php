<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    /** @use HasFactory<\Database\Factories\SubdistrictFactory> */
    use HasFactory;

    protected $fillable = [
        "nama",
        "fullCode",
        "code"
    ];

    // contoh
    //  {
    //    "nama": "BUBON",
    //    "fullCode": "110506",
    //    "code": null
    //  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    /** @use HasFactory<\Database\Factories\ProvinceFactory> */
    use HasFactory;

    protected $fillable = [
        "nama",
        "fullCode",
        "code"
    ];

    // contoh
    //  {
    //    "nama": "ACEH",
    //    "fullCode": "11",
    //    "code": "11"
    //  }
}

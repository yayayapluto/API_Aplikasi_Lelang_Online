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

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }
}

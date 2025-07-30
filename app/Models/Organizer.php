<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizerFactory> */
    use HasFactory;

    protected $fillable = [
        "nama_bank",
        "nomor_telepon",
        "alamat",
        "nama_unit_kerja"
    ];

    public function kpknl()
    {
        return $this->belongsTo(Kpknl::class);
    }
}

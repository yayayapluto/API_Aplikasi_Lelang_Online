<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_lot',
        'nilai_limit',
        'nilai_jaminan',
        'kode_lot',
        'tanggal_batas_jaminan',
        'province_id',
        'kpknl_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'cara_penawaran',
    ];

    protected $casts = [
        'tanggal_batas_jaminan' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'nilai_limit' => 'integer',
        'nilai_jaminan' => 'integer',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function kpknl()
    {
        return $this->belongsTo(Kpknl::class);
    }
}

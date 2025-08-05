<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'bukti_kepemilikan',
        'bukti_kepemilikan_no',
        'bukti_kepemilikan_tgl',
        'alamat',
        'luas',
        'stnk',
        'nomor_rangka',
        'nopol',
        'tahun',
        'warna',
        'item_type_id',
        'object_type_id',
        'category_id',
    ];

    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function objectType()
    {
        return $this->belongsTo(ObjectType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function photos()
    {
        return $this->belongsToMany(File::class, 'item_photos', 'item_id', 'file_id');
    }
}

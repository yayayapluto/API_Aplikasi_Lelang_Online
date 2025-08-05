<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use function Symfony\Component\Translation\t;

class itemPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\ItemPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        "file_id",
        "item_id"
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

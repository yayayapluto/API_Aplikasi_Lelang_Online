<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionPhotoFactory> */
    use HasFactory;

    /**
     * Schema::create('auction_photos', function (Blueprint $table) {
     * $table->id();
     * $table->foreignId("auction_id")->constrained("auctions")->cascadeOnDelete();
     * $table->foreignId("file_id")->constrained("files")->cascadeOnDelete();
     * $table->timestamps();
     * });
     */

    protected $fillable = [
        "auction_id",
        "file_id"
    ];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}

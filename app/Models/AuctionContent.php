<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'item_id',
        'seller_id',
        'organizer_id',
    ];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }
}

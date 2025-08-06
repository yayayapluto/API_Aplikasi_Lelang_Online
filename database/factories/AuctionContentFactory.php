<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Item;
use App\Models\Organizer;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionContentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Safely get random IDs, fallback to random if no records
        $getRandomId = function ($model) {
            return $model::inRandomOrder()->value('id') ?? 1;
        };

        return [
            'auction_id' => $getRandomId(Auction::class),
            'item_id' => $getRandomId(Item::class),
            'seller_id' => $getRandomId(Seller::class),
            'organizer_id' => $getRandomId(Organizer::class),
        ];
    }
}

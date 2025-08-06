<?php

namespace Database\Factories;

use App\Models\Auction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuctionPhoto>
 */
class AuctionPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "auction_id" => Auction::query()->pluck("id")->random(),
            "file_id" => Auction::query()->pluck("id")->random()
        ];
    }
}

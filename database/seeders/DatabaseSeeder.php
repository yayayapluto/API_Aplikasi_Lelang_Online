<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\File;
use App\Models\Item;
use App\Models\itemPhoto;
use App\Models\ItemType;
use App\Models\JobType;
use App\Models\Kpknl;
use App\Models\ObjectType;
use App\Models\Organizer;
use App\Models\Province;
use App\Models\Seller;
use App\Models\Subdistrict;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Village;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Country::factory(50)->create();
        Province::factory(50)->create();
        City::factory(50)->create();
        Subdistrict::factory(50)->create();
        Village::factory(50)->create();
        JobType::factory(50)->create();
        Category::factory(50)->create();
        ItemType::factory(50)->create();
        ObjectType::factory(50)->create();
        File::factory(50)->create();

        Kpknl::factory(50)->create();
        Organizer::factory(50)->create();

        User::factory(50)->create();
        Seller::factory(50)->create();

        Item::factory(50)->create();
        ItemPhoto::factory(50)->create();

        Auction::factory(50)->create();
    }
}

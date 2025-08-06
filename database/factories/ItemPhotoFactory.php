<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\itemPhoto>
 */
class ItemPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "file_id" => File::query()->pluck("id")->random(),
            "item_id" => Item::query()->pluck("id")->random(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => "Seller " . $this->faker->unique()->words(3, true),
            "nomor_telepon" => $this->faker->unique()->phoneNumber(),
            "alamat" => $this->faker->streetAddress(),
            "province_id" => Province::query()->pluck("id")->random(),
            "city_id" => City::query()->pluck("id")->random()
        ];
    }
}

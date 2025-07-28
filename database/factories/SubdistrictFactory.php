<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subdistrict>
 */
class SubdistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => "Kecamatan " . $this->faker->unique()->word(),
            "city_id" => City::query()->pluck("id")->random()
        ];
    }
}

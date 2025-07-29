<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => $this->faker->unique()->country(),
            "kode" => $this->faker->unique()->countryCode(),
            "nomor" => $this->faker->unique()->numberBetween(10, 99)
        ];
    }
}

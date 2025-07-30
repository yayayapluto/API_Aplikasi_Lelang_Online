<?php

namespace Database\Factories;

use App\Models\Subdistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Village>
 */
class VillageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nama" => "Kelurahan " . $this->faker->unique()->words(2, true),
            "fullCode" => $this->faker->numerify("########"),
            "code" => null,
            "kode_pos" => $this->faker->numerify("#####")
        ];
    }
}
